<?php

namespace App\GaelO\UseCases\ValidateDicomUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelOValidateDicomException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\RegisterDicomStudyService;
use App\GaelO\Services\TusService;
use App\GaelO\Services\VisitService;
use App\GaelO\Util;
use Exception;
use ZipArchive;

class ValidateDicomUpload
{

    private AuthorizationVisitService $authorizationService;
    private TusService $tusService;
    private OrthancService $orthancService;
    private RegisterDicomStudyService $registerDicomStudyService;
    private VisitService $visitService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(
        AuthorizationVisitService $authorizationService,
        TusService $tusService,
        OrthancService $orthancService,
        RegisterDicomStudyService $registerDicomStudyService,
        VisitService $visitService,
        PatientRepositoryInterface $patientRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->authorizationService = $authorizationService;
        $this->registerDicomStudyService = $registerDicomStudyService;
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->tusService = $tusService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse)
    {

        try {
            //Retrieve Visit Context
            $this->visitService->setVisitId($validateDicomUploadRequest->visitId);
            $visitEntity = $this->visitService->getVisitContext();
            $patientId = $visitEntity['patient_id'];
            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $patientCode = $patientEntity['code'];
            //$patientCode = $visitEntity['patient_id'];
            $uploadStatus = $visitEntity['upload_status'];
            $studyName = $visitEntity['patient']['study_name'];
            $visitType = $visitEntity['visit_type']['name'];
            $anonProfile = $visitEntity['visit_type']['anon_profile'];
            $visitStatus = $visitEntity['status_done'];

            $currentUserId = $validateDicomUploadRequest->currentUserId;
            $visitId  = $validateDicomUploadRequest->visitId;

            $this->checkAuthorization($currentUserId, $visitId, $uploadStatus, $studyName, $visitStatus);

            //Make Visit as being upload processing
            $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_PROCESSING);

            //Create Temporary folder to work
            $unzipedPath = sys_get_temp_dir() . '/GaelO_Upload_' . random_int(10000, 99999) . '_' . $currentUserId;
            if (is_dir($unzipedPath)) {
                unlink($unzipedPath);
            } else {
                mkdir($unzipedPath, 0755);
            }

            //Get uploaded Zips from TUS and upzip it in a temporary folder
            foreach ($validateDicomUploadRequest->uploadedFileTusId as $tusFileId) {
                $tusTempZip = $this->tusService->getZip($tusFileId);

                $zipSize = filesize($tusTempZip);
                $uncompressedzipSize = Util::getZipUncompressedSize($tusTempZip);
                if ($uncompressedzipSize / $zipSize > 50) {
                    throw new GaelOValidateDicomException("Bomb Zip");
                }

                $zip = new ZipArchive();
                $zip->open($tusTempZip);
                $zip->extractTo($unzipedPath);
                $zip->close();

                //Remove file from TUS and downloaded temporary zip
                $this->tusService->deleteZip($tusFileId);
                unlink($tusTempZip);
            }
            $this->orthancService->setOrthancServer(false);

            $importedOrthancStudyID = $this->sendFolderToOrthanc($unzipedPath, $validateDicomUploadRequest->numberOfInstances);

            //Anonymize and store new anonymized study Orthanc ID
            $anonymizedOrthancStudyID = $this->orthancService->anonymize(
                $importedOrthancStudyID,
                $anonProfile,
                $patientCode,
                $patientId,
                $visitType,
                $studyName
            );

            //Delete original import
            $this->orthancService->deleteFromOrthanc("studies", $importedOrthancStudyID);

            //Send to Orthanc Pacs and fill the database
            $this->orthancService->sendToPeer("OrthancPacs", [$anonymizedOrthancStudyID], true);

            //erase transfered anonymized study from orthanc exposed
            $this->orthancService->deleteFromOrthanc("studies", $anonymizedOrthancStudyID);

            //Switch to Orthanc PACS to check images and fill database
            $this->orthancService->setOrthancServer(true);

            $statistics = $this->orthancService->getOrthancRessourcesStatistics('studies', $anonymizedOrthancStudyID);
            if ($statistics['CountInstances'] !== $validateDicomUploadRequest->numberOfInstances) {
                throw new GaelOValidateDicomException("Error during Peer transfers");
            }

            //Fill DB with studies /series information
            $this->registerDicomStudyService->setData(
                $visitId,
                $studyName,
                $currentUserId,
                $anonymizedOrthancStudyID,
                $validateDicomUploadRequest->originalOrthancId
            );

            $studyInstanceUID = $this->registerDicomStudyService->execute();

            //Change Visit status
            $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_DONE);

            //Write success in Tracker
            $actionDetails = [
                'studyInstanceUID' => $studyInstanceUID
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_INVESTIGATOR,
                $studyName,
                $visitId,
                Constants::TRACKER_UPLOAD_SERIES,
                $actionDetails
            );

            $validateDicomUploadResponse->status = 200;
            $validateDicomUploadResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $this->handleImportException(
                $e->getMessage(),
                $visitId,
                $patientId,
                $visitType,
                $unzipedPath,
                $studyName,
                $currentUserId
            );

            $validateDicomUploadResponse->status = $e->statusCode;
            $validateDicomUploadResponse->statusText = $e->statusText;
            $validateDicomUploadResponse->body = $e->getErrorBody();
        } catch (Exception $e) {

            $this->handleImportException(
                $e->getMessage(),
                $visitId,
                $patientId,
                $visitType,
                $unzipedPath,
                $studyName,
                $currentUserId
            );

            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $uploadStatus, string $studyName, string $visitStatus): void
    {

        $this->authorizationService->setUserId($currentUserId);

        $this->authorizationService->setStudyName($studyName);
        $this->authorizationService->setVisitId($visitId);
        if (!$this->authorizationService->isVisitAllowed(Constants::ROLE_INVESTIGATOR) || $uploadStatus !== Constants::UPLOAD_STATUS_NOT_DONE || $visitStatus !== Constants::VISIT_STATUS_DONE) {
            throw new GaelOForbiddenException();
        }
    }

    /**
     * Send folder content to orthanc,
     * Checks that imported dicom match number of expected dicoms
     * returns OrthancStudyID
     */
    private function sendFolderToOrthanc(string $unzipedPath, int $numberOfInstances): string
    {

        //Recursive scann of the unzipped folder
        $filesArray = Util::getPathAsFileArray($unzipedPath);

        if (sizeof($filesArray) != $numberOfInstances) {
            throw new GaelOValidateDicomException("Number Of Uploaded Files dosen't match expected instance number");
        }

        $importedMap = [];

        $uploadSuccessResponseArray = $this->orthancService->importFiles($filesArray);

        //Import dicom file one by one
        foreach ($uploadSuccessResponseArray as $response) {
            $importedMap[$response['ParentStudy']][$response['ParentSeries']][] = $response['ID'];
        }

        $numberOfImportedInstances = sizeof($uploadSuccessResponseArray);

        //Delete original file after import
        Util::recursiveDirectoryDelete($unzipedPath);

        if (count($importedMap) == 1 && $numberOfImportedInstances === $numberOfInstances) {
            return array_key_first($importedMap);
        } else {
            //These error shall never occur
            if (count($importedMap) > 1) {
                throw new GaelOValidateDicomException("More than one study in Zip");
            } else if ($numberOfImportedInstances !== $numberOfInstances) {
                throw new GaelOValidateDicomException("Imported DICOM not matching announced number of Instances");
            }
        }
    }

    /**
     * Handler if an exception occurs during validation
     * Reset upload status of visit to Not Done
     * Write Failure in Tracker
     * Send warning emails to administrators
     */
    private function handleImportException(string $errorMessage, int $visitId, string $patientId, string $visitType,  string $unzipedPath, string $studyName, int $userId)
    {

        $this->visitService->updateUploadStatus(Constants::UPLOAD_STATUS_NOT_DONE);

        $actionDetails = [
            'reason' => $errorMessage
        ];
        $this->trackerRepositoryInterface->writeAction($userId, Constants::ROLE_INVESTIGATOR, $studyName, $visitId, Constants::TRACKER_UPLOAD_VALIDATION_FAILED, $actionDetails);

        $this->mailServices->sendValidationFailMessage(
            $visitId,
            $patientId,
            $visitType,
            $studyName,
            $unzipedPath,
            $userId,
            $errorMessage
        );

        if (is_dir($unzipedPath)) Util::recursiveDirectoryDelete($unzipedPath);
    }
}

<?php

namespace App\GaelO\UseCases\ValidateDicomUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Constants\Enums\VisitStatusDoneEnum;
use App\GaelO\Exceptions\AbstractGaelOException;
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

            //Set Time Limit at 30min as operation could be really long
            set_time_limit(1800);

            //Retrieve Visit Context
            $this->visitService->setVisitId($validateDicomUploadRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();
            $patientId = $visitContext['patient_id'];
            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $patientCode = $patientEntity['code'];
            $studyName = $visitContext['patient']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $anonProfile = $visitContext['visit_type']['anon_profile'];

            $currentUserId = $validateDicomUploadRequest->currentUserId;
            $visitId  = $validateDicomUploadRequest->visitId;

            $expectedNumberOfInstances = $validateDicomUploadRequest->numberOfInstances;
            $originalOrthancId = $validateDicomUploadRequest->originalOrthancId;

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);

            //Make Visit as being upload processing
            $this->visitService->updateUploadStatus(UploadStatusEnum::PROCESSING->value);

            //Create Temporary folder to work
            $unzipedPath = Util::getUploadTemporaryFolder();

            //Get uploaded Zips from TUS and upzip it in a temporary folder
            foreach ($validateDicomUploadRequest->uploadedFileTusId as $tusFileId) {
                $tusTempZip = $this->tusService->getFile($tusFileId);

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
                $this->tusService->deleteFile($tusFileId);
                unlink($tusTempZip);
            }
            $this->orthancService->setOrthancServer(false);

            $orthancStudyImport = $this->orthancService->importDicomFolder($unzipedPath);
            if ($expectedNumberOfInstances !== $orthancStudyImport->getNumberOfInstances()) {
                throw new GaelOValidateDicomException("Imported DICOM not matching announced number of Instances");
            }
            $importedOrthancStudyID = $orthancStudyImport->getStudyOrthancId();


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
            if ($statistics['CountInstances'] !== $expectedNumberOfInstances) {
                throw new GaelOValidateDicomException("Error during Peer transfers");
            }

            //Fill DB with studies /series information
            $this->registerDicomStudyService->setData(
                $visitId,
                $studyName,
                $currentUserId,
                $anonymizedOrthancStudyID,
                $originalOrthancId
            );

            $studyInstanceUID = $this->registerDicomStudyService->execute();

            //Change Visit status
            $this->visitService->updateUploadStatus(UploadStatusEnum::DONE->value);

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
        } catch (AbstractGaelOException $e) {
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

    private function checkAuthorization(int $currentUserId, int $visitId, string $studyName, array $visitContext): void
    {
        $visitStatus = $visitContext['status_done'];
        $uploadStatus = $visitContext['upload_status'];
        $this->authorizationService->setUserId($currentUserId);
        $this->authorizationService->setStudyName($studyName);
        $this->authorizationService->setVisitId($visitId);
        $this->authorizationService->setVisitContext($visitContext);
        if (!$this->authorizationService->isVisitAllowed(Constants::ROLE_INVESTIGATOR) || $uploadStatus !== UploadStatusEnum::NOT_DONE->value || $visitStatus !== VisitStatusDoneEnum::DONE->value) {
            throw new GaelOForbiddenException();
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

        $this->visitService->updateUploadStatus(UploadStatusEnum::NOT_DONE->value);

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

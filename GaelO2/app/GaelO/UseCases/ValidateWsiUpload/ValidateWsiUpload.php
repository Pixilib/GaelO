<?php

namespace App\GaelO\UseCases\ValidateWsiUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Constants\Enums\VisitStatusDoneEnum;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelOValidateWsiException;
use App\GaelO\Services\GaelOWsiProcessingService\GaelOWsiProcessingService;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\RegisterDicomStudyService;
use App\GaelO\Services\TusService;
use App\GaelO\Services\VisitService;
use App\GaelO\Util;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

class ValidateWsiUpload
{

    private array $wsiFiles = [];
    private AuthorizationVisitService $authorizationService;
    private TusService $tusService;
    private OrthancService $orthancService;
    private GaelOWsiProcessingService $gaelOWsiProcessingService;
    private RegisterDicomStudyService $registerDicomStudyService;
    private VisitService $visitService;
    private PatientRepositoryInterface $patientRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;
    private bool $markedProcessing = false;

    public function __construct(
        AuthorizationVisitService $authorizationService,
        TusService $tusService,
        GaelOWsiProcessingService $gaelOWsiProcessingService,
        OrthancService $orthancService,
        RegisterDicomStudyService $registerDicomStudyService,
        VisitService $visitService,
        PatientRepositoryInterface $patientRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->authorizationService = $authorizationService;
        $this->registerDicomStudyService = $registerDicomStudyService;
        $this->gaelOWsiProcessingService = $gaelOWsiProcessingService;
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->tusService = $tusService;
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(ValidateWsiUploadRequest $validateWsiUploadRequest, ValidateWsiUploadResponse $validateWsiUploadResponse)
    {

        try {
            //Run as a background task even if the user leave the website
            ignore_user_abort(true);

            //Initialize unzipPath to avoid null pointer in catch
            $unzipedPath = null;
            //Retrieve Visit Context
            $this->visitService->setVisitId($validateWsiUploadRequest->visitId);
            $visitContext = $this->visitService->getVisitContext();
            $patientId = $visitContext['patient_id'];
            $patientEntity = $this->patientRepositoryInterface->find($patientId);
            $patientCode = $patientEntity['code'];
            $studyName = $visitContext['patient']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $anonProfile = $visitContext['visit_type']['anon_profile'];

            $currentUserId = $validateWsiUploadRequest->currentUserId;
            $visitId  = $validateWsiUploadRequest->visitId;

            $this->checkAuthorization($currentUserId, $visitId, $studyName, $visitContext);
            $this->visitService->setCurrentUserId($currentUserId);

            //Make Visit as being upload processing
            $this->visitService->updateUploadStatus(UploadStatusEnum::PROCESSING->value);
            $this->markedProcessing = true;

            //Set Time Limit at 30min as operation could be really long
            set_time_limit(1800);
            //Create Temporary folder to work
            $unzipedPath = Util::getUploadTemporaryFolder();
            //Retrieve uploaded wsi from tus
            foreach ($validateWsiUploadRequest->uploadedFileTusId as $tusFileId) {
                $fileName = $this->tusService->getFile($tusFileId);
                $metadata = $this->tusService->getMetadata($tusFileId);
                $this->wsiFiles[] = [
                    'filename' => $fileName,
                    'metadata' => $metadata
                ];
                $this->tusService->deleteFile($tusFileId);
            }
            Log::info($this->wsiFiles);
            for ($i = 0 ; $i < sizeof($this->wsiFiles) ; $i++){
                $wsiProcessingId = $this->gaelOWsiProcessingService->postWsiImage($this->wsiFiles[$i]['filename']);
                $this->wsiFiles[$i]['wsiProcessingId'] = $wsiProcessingId;
            }
            
            Log::info($this->wsiFiles);
            $this->purgeTemporaryWsiFiles();

            $wsiSildes = [];
            foreach ($this->wsiFiles as $file) {
                $wsiSildes[] = [
                    'wsi_id' => $file['wsiProcessingId'],
                    'SeriesDescription' =>  $file['metadata']['SeriesDescription'] ?? 'wsi',
                    'SeriesNumber' => $file['metadata']['SeriesNumber'] ?? '1'
                    
                ];
            }

            $responseCreateDicom = $this->gaelOWsiProcessingService->convertToDicom(
                $wsiSildes,
                $patientId,
                $patientCode,
                $visitType,
                "GaelO"
            );

            $expectedNumberOfInstances = $responseCreateDicom['number_of_instances'];
            $studyInstanceUID = $responseCreateDicom['study_instance_uid'];
            $originalOrthancId = $responseCreateDicom['study_orthanc_id'];

            $this->orthancService->setOrthancServer(false);

            //Retrieve created DICOMs
            $dicomZip = $this->gaelOWsiProcessingService->getDicom($studyInstanceUID);
            //Unzip dicoms to a temporary folder
            $unzipedPath = Util::getUploadTemporaryFolder();
            $zip = new ZipArchive();
            $zip->open($dicomZip);
            $zip->extractTo($unzipedPath);
            $zip->close();

            unlink($dicomZip);
            $this->gaelOWsiProcessingService->deleteWsi($wsiProcessingId);

            $this->gaelOWsiProcessingService->deleteDicom($studyInstanceUID);

            $orthancStudyImport = $this->orthancService->importDicomFolder($unzipedPath);
            $importedNumberOfInstances = $orthancStudyImport->getNumberOfInstances();
            $importedOrthancStudyID = $orthancStudyImport->getStudyOrthancId();

            if ($expectedNumberOfInstances !== $importedNumberOfInstances) {
                $this->orthancService->deleteFromOrthanc("studies", $importedOrthancStudyID);
                throw new GaelOValidateWsiException("Imported DICOM (" . $importedNumberOfInstances . ") not matching announced number of Instances (" . $expectedNumberOfInstances . ")");
            }

            //Anonymize and store new anonymized study Orthanc ID
            $anonymizedOrthancStudyID = $this->orthancService->anonymize(
                $importedOrthancStudyID,
                $anonProfile,
                $patientCode,
                $patientId,
                $visitType,
                $studyName,
                null
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
                throw new GaelOValidateWsiException("Error during Peer transfers");
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
            $this->markedProcessing = false;

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

            $validateWsiUploadResponse->status = 200;
            $validateWsiUploadResponse->statusText = 'OK';
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

            $validateWsiUploadResponse->status = $e->statusCode;
            $validateWsiUploadResponse->statusText = $e->statusText;
            $validateWsiUploadResponse->body = $e->getErrorBody();
        } catch (Throwable $e) {

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

    private function purgeTemporaryWsiFiles()
    {
        foreach ($this->wsiFiles as $file) {
            unlink($file['filename']);
        }
    }

    /**
     * Handler if an exception occurs during validation
     * Reset upload status of visit to Not Done
     * Write Failure in Tracker
     * Send warning emails to administrators
     */
    private function handleImportException(string $errorMessage, int $visitId, string $patientId, string $visitType,  ?string $unzipedPath, string $studyName, int $userId)
    {

        //Restore upload not done status if it has been updated to processing status
        if ($this->markedProcessing) {
            $this->visitService->updateUploadStatus(UploadStatusEnum::NOT_DONE->value);
        }

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

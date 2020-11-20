<?php

namespace App\GaelO\UseCases\ValidateDicomUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Exceptions\GaelOValidateDicomException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\PathService;
use App\GaelO\Services\RegisterOrthancStudyService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\TusService;
use App\GaelO\Services\VisitService;
use Exception;
use ZipArchive;

class ValidateDicomUpload{

    public function __construct(AuthorizationService $authorizationService,
                        TusService $tusService,
                        OrthancService $orthancService,
                        RegisterOrthancStudyService $registerOrthancStudyService,
                        VisitService $visitService,
                        TrackerService $trackerService,
                        MailServices $mailServices)
    {
        $this->authorizationService = $authorizationService;
        $this->registerOrthancStudyService = $registerOrthancStudyService;
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->tusService = $tusService;
        $this->trackerService = $trackerService;
        $this->mailServices = $mailServices;
    }

    public function execute(ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse){

        try{
            //Retrieve Visit Context
            $visitData  = $this->visitService->getVisitData($validateDicomUploadRequest->visitId);
            $patientCode = $visitData['patient_code'];
            $uploadStatus = $visitData['upload_status'];
            $visitEntity = $this->visitService->getVisitContext($validateDicomUploadRequest->visitId);
            $studyName = $visitEntity['visit_group']['study_name'];
            $visitType = $visitEntity['visit_type']['name'];
            $visitGroup =  $visitEntity['visit_group']['modality'];
            $anonProfile = $visitEntity['visit_type']['anon_profile'];

            //TODO Authorization : Check Investigator Role, and patient is in affiliated center of user, and status upload not done, and visit status done
            $this->checkAuthorization($validateDicomUploadRequest->currentUserId, $validateDicomUploadRequest->visitId, $uploadStatus);
            //Make Visit as being upload processing
            $this->visitService->updateUploadStatus($validateDicomUploadRequest->visitId, Constants::UPLOAD_STATUS_PROCESSING, $validateDicomUploadRequest->currentUserId );

            //Create Temporary folder to work
            $unzipedPath = sys_get_temp_dir().'/GaelO_Upload_'.mt_rand(10000, 99999).'_'.$validateDicomUploadRequest->currentUserId;
            if (is_dir($unzipedPath)) {
                unlink($unzipedPath);
            }else{
                mkdir($unzipedPath, 0755);
            }

            //Get uploaded Zips from TUS and upzip it in a temporary folder
            foreach($validateDicomUploadRequest->uploadedFileTusId as $tusFileId){
                $tusTempZip = $this->tusService->getZip($tusFileId);

                $zipSize=filesize($tusTempZip);
                $uncompressedzipSize=PathService::getZipUncompressedSize($tusTempZip);
                if ($uncompressedzipSize/$zipSize > 50) {
                    throw new GaelOValidateDicomException("Bomb Zip");
                }

                $zip=new ZipArchive;
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
            $anonymizedOrthancStudyID=$this->orthancService->anonymize($importedOrthancStudyID,
                                        $anonProfile,
                                        $patientCode,
                                        $visitType,
                                        $studyName);

            //Delete original import
            $this->orthancService->deleteFromOrthanc("studies", $importedOrthancStudyID);

            //Send to Orthanc Pacs and fill the database
            $this->orthancService->sendToPeer("OrthancPacs", [$anonymizedOrthancStudyID], true);

            //erase transfered anonymized study from orthanc exposed
            $this->orthancService->deleteFromOrthanc("studies", $anonymizedOrthancStudyID);

            //Switch to Orthanc PACS to check images and fill database
            $this->orthancService->setOrthancServer(true);

            $studyOrthancDetails = $this->orthancService->getOrthancRessourcesDetails('studies', $anonymizedOrthancStudyID);
            $statistics = $this->orthancService->getOrthancRessourcesStatistics('studies', $anonymizedOrthancStudyID);
            if($statistics['CountInstances'] !== $validateDicomUploadRequest->numberOfInstances){
                throw new GaelOValidateDicomException("Error during Peer transfers");
            }

            //Fill DB with studies /series information
            $this->registerOrthancStudyService->setData(
                $validateDicomUploadRequest->visitId,
                $studyName,
                $validateDicomUploadRequest->currentUserId,
                $anonymizedOrthancStudyID,
                $validateDicomUploadRequest->originalOrthancId
            );

            $this->registerOrthancStudyService->execute();

            //Change Visit status
            $this->visitService->updateUploadStatus($validateDicomUploadRequest->visitId, Constants::UPLOAD_STATUS_DONE, $validateDicomUploadRequest->currentUserId);

            //Write success in Tracker
            $actionDetails = [
                'uploadedSeries'=>$studyOrthancDetails['Series'],
                'patientCode'=> $patientCode,
                'visitType'=>$visitType,
                'visitGroup'=>$visitGroup
            ];

            $this->trackerService->writeAction($validateDicomUploadRequest->currentUserId,
                            Constants::ROLE_INVESTIGATOR,
                            $studyName,
                            $validateDicomUploadRequest->visitId,
                            Constants::TRACKER_UPLOAD_SERIES,
                            $actionDetails);

            $validateDicomUploadResponse->status = 200;
            $validateDicomUploadResponse->statusText = 'OK';

        } catch (GaelOException $e){
            $this->handleImportException($e->getMessage(), $validateDicomUploadRequest->visitId,
                        $patientCode, $visitType, $unzipedPath, $studyName, $validateDicomUploadRequest->currentUserId);

            $validateDicomUploadResponse->status = $e->statusCode;
            $validateDicomUploadResponse->statusText = $e->statusText;
            $validateDicomUploadResponse->body = $e->getErrorBody();

        } catch (Exception $e){

            $this->handleImportException($e->getMessage(), $validateDicomUploadRequest->visitId,
                        $patientCode, $visitType, $unzipedPath, $studyName, $validateDicomUploadRequest->currentUserId);

            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $uploadStatus ) : void {

        $this->authorizationService->setCurrentUser($currentUserId);
        if( ! $this->authorizationService->isVisitAllowed($visitId, Constants::ROLE_INVESTIGATOR ) || $uploadStatus !== Constants::UPLOAD_STATUS_NOT_DONE){
            throw new GaelOForbiddenException();
        };

    }

    /**
     * Send folder content to orthanc,
     * Checks that imported dicom match number of expected dicoms
     * returns OrthancStudyID
     */
    private function sendFolderToOrthanc(string $unzipedPath, int $numberOfInstances)  : string {

        //Recursive scann of the unzipped folder
        $filesArray = PathService::getPathAsFileArray($unzipedPath);

        if(sizeof($filesArray) != $numberOfInstances){
            throw new GaelOValidateDicomException("Number Of Uploaded Files dosen't match expected instance number");
        }

        $importedMap=[];

        $uploadSuccessResponseArray = $this->orthancService->importFiles($filesArray);

        //Import dicom file one by one
        foreach ($uploadSuccessResponseArray as $response) {
            $importedMap[$response['ParentStudy']][$response['ParentSeries']][]=$response['ID'];
        }

        $numberOfImportedInstances = sizeof($uploadSuccessResponseArray);

        //Delete original file after import
        PathService::recursive_directory_delete($unzipedPath);

        if (count($importedMap) == 1 && $numberOfImportedInstances === $numberOfInstances) {
            return array_key_first ($importedMap);
        }else {
            //These error shall never occur
            if (count($importedMap) > 1) {
                throw new GaelOValidateDicomException("More than one study in Zip");
            }else if ($numberOfImportedInstances !== $numberOfInstances) {
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
    private function handleImportException(string $errorMessage, int $visitId, string $patientCode, string $visitType,  string $unzipedPath, string $studyName, int $userId) {

        $this->visitService->updateUploadStatus($visitId, Constants::UPLOAD_STATUS_NOT_DONE, $userId);

        $actionDetails = [
            'reason'=>$errorMessage
        ];
        $this->trackerService->writeAction($userId, Constants::ROLE_INVESTIGATOR, $studyName, $visitId, Constants::TRACKER_UPLOAD_VALIDATION_FAILED, $actionDetails);

        $this->mailServices->sendValidationFailMessage($visitId, $patientCode, $visitType,
                $studyName, $unzipedPath, $userId, $errorMessage);

        unlink($unzipedPath);
    }

}

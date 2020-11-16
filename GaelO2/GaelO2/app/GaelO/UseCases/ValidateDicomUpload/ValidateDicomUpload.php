<?php

namespace App\GaelO\UseCases\ValidateDicomUpload;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\RegisterOrthancStudyService;
use App\GaelO\Services\VisitService;
use Exception;

class ValidateDicomUpload{

    public function __construct(AuthorizationService $authorizationService, OrthancService $orthancService,
                        RegisterOrthancStudyService $registerOrthancStudyService,
                        VisitService $visitService)
    {
        $this->authorizationService = $authorizationService;
        $this->registerOrthancStudyService = $registerOrthancStudyService;
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
    }

    public function execute(ValidateDicomUploadRequest $validateDicomUploadRequest, ValidateDicomUploadResponse $validateDicomUploadResponse){

        try{

            //Authorization : Check Investigator Role, and patient is in affiliated center of user, and status upload not done, and visit status done
            //Get Zip from TUS and upzip it in a temporary folder
            //Import files in temporary Orthanc, check number of instance imported to Orthanc
            //Anonymize in temporary Orthanc
            // Send to Orthanc PACS, check completness

            $anonymizedOrthancStudyId = "7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f";
            //Fill DB with studies /series information
            $this->registerOrthancStudyService->setData(
                false,
                $validateDicomUploadRequest->visitId,
                $validateDicomUploadRequest->currentUserId,
                $anonymizedOrthancStudyId,
                $validateDicomUploadRequest->originalOrthancId
            );
            $this->registerOrthancStudyService->execute();

            //Change Visit status
            $this->visitService->updateUploadStatus($validateDicomUploadRequest->visitId, Constants::UPLOAD_STATUS_DONE, $validateDicomUploadRequest->currentUserId);

            $validateDicomUploadResponse->status = 200;
            $validateDicomUploadResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $validateDicomUploadResponse->status = $e->statusCode;
            $validateDicomUploadResponse->statusText = $e->statusText;
            $validateDicomUploadResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

}

<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetDicomsFileSupervisor {

    private AuthorizationService $authorizationService;

    public function __construct( AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;

    }

    public function execute(GetDicomsFileSupervisorRequest $getDicomsFileSupervisorRequest, GetDicomsFileSupervisorResponse $getDicomsFileSupervisorResponse){

        try{

            //Checker Authorization
            $this->checkAuthorization($getDicomsFileSupervisorRequest->currentUserId, $getDicomsFileSupervisorRequest->studyName);


            //First output the filename, then the controller will call outputStream to get content of orthanc response
            //$getDicomsResponse->filename = 'DICOM_'.$studyName.'_'.$visitGroup.'_'.$visitType.'_'.$patientCode.'.zip';

            $getDicomsFileSupervisorResponse->status = 200;
            $getDicomsFileSupervisorResponse->statusText = 'OK';

        }catch (GaelOException $e){
            $getDicomsFileSupervisorResponse->status = $e->statusCode;
            $getDicomsFileSupervisorResponse->statusText = $e->statusText;
            $getDicomsFileSupervisorResponse->body = $e->getErrorBody();
        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId, string $studyName){

        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }

}

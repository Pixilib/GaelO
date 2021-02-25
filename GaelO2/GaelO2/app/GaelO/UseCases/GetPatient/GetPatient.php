<?php

namespace App\GaelO\UseCases\GetPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;
use Exception;

class GetPatient {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationPatientService $authorizationService;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface, AuthorizationPatientService $authorizationService){
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetPatientRequest $getPatientRequest, GetPatientResponse $getPatientResponse) : void
    {
        try{
            $code = $getPatientRequest->code;

            $this->checkAuthorization($getPatientRequest->currentUserId, $getPatientRequest->role, $code );
            $dbData = $this->patientRepositoryInterface->getPatientWithCenterDetails($code);

            $responseEntity = PatientEntity::fillFromDBReponseArray($dbData);

            //If Reviewer hide patient's center
            if( $getPatientRequest->role === Constants::ROLE_REVIEWER){
                $responseEntity->centerCode = null;
            }else{
                $responseEntity->fillCenterDetails($dbData['center']['name'], $dbData['center']['country_code']);
            }

            $getPatientResponse->body = $responseEntity;
            $getPatientResponse->status = 200;
            $getPatientResponse->statusText = 'OK';

        } catch  (GaelOException $e){

            $getPatientResponse->status = $e->statusCode;
            $getPatientResponse->statusText = $e->statusText;
            $getPatientResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserid, string $role, int $patientCode ){
        $this->authorizationService->setCurrentUserAndRole($currentUserid, $role);
        $this->authorizationService->setPatient($patientCode);
        if( ! $this->authorizationService->isPatientAllowed() ){
            throw new GaelOForbiddenException();
        };

    }

}

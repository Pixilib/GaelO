<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\PatientService;
use Exception;

class GetCreatableVisits{

    private AuthorizationPatientService $authorizationPatientService;
    private PatientService $patientService;

    public function __construct(AuthorizationPatientService $authorizationPatientService, PatientService $patientService)
    {
        $this->authorizationPatientService = $authorizationPatientService;
        $this->patientService = $patientService;
    }

    public function execute(GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse){

        try{
            //SK A PASSER VIA ENTITY
            $this->checkAuthorization($getCreatableVisitsRequest->currentUserId, $getCreatableVisitsRequest->patientCode);
            $this->patientService->setPatientCode($getCreatableVisitsRequest->patientCode);
            $visitToCreate = $this->patientService->getAvailableVisitToCreate();
            $getCreatableVisitsResponse->status = 200;
            $getCreatableVisitsResponse->statusText = 'OK';
            $getCreatableVisitsResponse->body = $visitToCreate;

        } catch (GaelOException $e){

            $getCreatableVisitsResponse->status = $e->statusCode;
            $getCreatableVisitsResponse->statusText = $e->statusText;
            $getCreatableVisitsResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $patientCode){
        $this->authorizationPatientService->setCurrentUserAndRole($userId, Constants::ROLE_INVESTIGATOR);
        $this->authorizationPatientService->setPatient($patientCode);
        if ( ! $this->authorizationPatientService->isPatientAllowed() ){
            throw new GaelOForbiddenException();
        }

    }

}

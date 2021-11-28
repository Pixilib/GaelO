<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
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
            $this->checkAuthorization($getCreatableVisitsRequest->currentUserId, $getCreatableVisitsRequest->patientId, $getCreatableVisitsRequest->studyName);
            $this->patientService->setPatientCode($getCreatableVisitsRequest->patientId);
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

    private function checkAuthorization(int $userId, string $patientId, string $studyName){
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setStudyName($studyName);
        $this->authorizationPatientService->setPatientId($patientId);
        if ( ! $this->authorizationPatientService->isPatientAllowed(Constants::ROLE_INVESTIGATOR) ){
            throw new GaelOForbiddenException();
        }

    }

}

<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\VisitService;
use Exception;

class GetCreatableVisits{

    private AuthorizationPatientService $authorizationPatientService;
    private VisitService $visitService;

    public function __construct(AuthorizationPatientService $authorizationPatientService, VisitService $visitService)
    {
        $this->authorizationPatientService = $authorizationPatientService;
        $this->visitService = $visitService;
    }

    public function execute(GetCreatableVisitsRequest $getCreatableVisitsRequest, GetCreatableVisitsResponse $getCreatableVisitsResponse){

        try{

            $this->checkAuthorization($getCreatableVisitsRequest->currentUserId, $getCreatableVisitsRequest->patientCode);

            $visitToCreate = $this->visitService->getAvailableVisitToCreate($getCreatableVisitsRequest->patientCode);

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

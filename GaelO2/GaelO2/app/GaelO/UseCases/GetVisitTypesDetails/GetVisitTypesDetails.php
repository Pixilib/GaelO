<?php

namespace App\GaelO\UseCases\GetVisitTypesDetails;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetVisitTypesDetails {

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationService $authorizationService){
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitTypesDetailsRequest $getVisitTypesDetailsRequest, GetVisitTypesDetailsResponse $getVisitTypesDetailsResponse){

        try{

            $this->checkAuthorization($getVisitTypesDetailsRequest->currentUserId, $getVisitTypesDetailsRequest->studyName);

            $visitTypesEntities = $this->visitTypeRepositoryInterface->getVisitTypesFromIdArray($getVisitTypesDetailsRequest->visitTypesIds);
            $responseArray = [];
            foreach($visitTypesEntities as $visitTypeEntity){
                $responseArray[] = VisitTypeEntity::fillFromDBReponseArray($visitTypeEntity);
            }

            $getVisitTypesDetailsResponse->body = $responseArray;
            $getVisitTypesDetailsResponse->status = 200;
            $getVisitTypesDetailsResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $getVisitTypesDetailsResponse->body = $e->getErrorBody();
            $getVisitTypesDetailsResponse->status = $e->statusCode;
            $getVisitTypesDetailsResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId, String $studyName)
    {
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if (!$this->authorizationService->isRoleAllowed($studyName)) {
            throw new GaelOForbiddenException();
        }
    }

}

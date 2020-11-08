<?php

namespace App\GaelO\UseCases\GetVisitType;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetVisitType {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse){

        try{

            $this->checkAuthorization($getVisitTypeRequest->currentUserId);
            $visitType = $this->persistenceInterface->find($getVisitTypeRequest->visitTypeId);
            $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
            $getVisitTypeResponse->body = $visitTypeEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e ){

            $getVisitTypeResponse->body = $e->getErrorBody();
            $getVisitTypeResponse->status = $e->statusCode;
            $$getVisitTypeResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUser($userId);
        if( ! $this->authorizationService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        }

    }

}

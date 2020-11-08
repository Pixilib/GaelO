<?php

namespace App\GaelO\UseCases\GetVisitGroup;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitTypeResponse){

        try{
            $this->checkAuthorization($getVisitGroupRequest->currentUserId);
            $visitGroupData = $this->persistenceInterface->find($getVisitGroupRequest->visitGroupId);
            $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupData);
            $getVisitTypeResponse->body = $visitGroupEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getVisitTypeResponse->body = $e->getErrorBody();
            $getVisitTypeResponse->status = $e->statusCode;
            $getVisitTypeResponse->statusText = $e->statusText;

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

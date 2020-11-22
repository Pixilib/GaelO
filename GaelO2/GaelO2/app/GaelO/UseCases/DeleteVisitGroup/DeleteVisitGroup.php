<?php

namespace App\GaelO\UseCases\DeleteVisitGroup;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteVisitGroupRequest $deleteVisitGroupRequest, DeleteVisitGroupResponse $deleteVisitGroupResponse){

        try{
            $this->checkAuthorization($deleteVisitGroupRequest->currentUserId);

            $hasVisitTypes = $this->persistenceInterface->hasVisitTypes($deleteVisitGroupRequest->visitGroupId);

            if($hasVisitTypes) throw new GaelOBadRequestException('Existing Child Visit Type');

            $this->persistenceInterface->delete($deleteVisitGroupRequest->visitGroupId);
            $deleteVisitGroupResponse->status = 200;
            $deleteVisitGroupResponse->statusText = 'OK';


        }catch (GaelOException $e){
            $deleteVisitGroupResponse->body = $e->getErrorBody();
            $deleteVisitGroupResponse->status = $e->statusCode;
            $deleteVisitGroupResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    public function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}

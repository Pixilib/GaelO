<?php

namespace App\GaelO\UseCases\GetUserRoles;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetUserRoles {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserRolesRequest $getUserRolesRequest, GetUserRolesResponse $getUserRoleResponse) : void {

        try{

            $this->checkAuthorization($getUserRolesRequest->currentUserId, $getUserRolesRequest->userId, $getUserRolesRequest->study);
            if( empty($getUserRolesRequest->study) ){
                $roles = $this->persistenceInterface->getUsersRoles($getUserRolesRequest->userId);
            }else {
                $roles = $this->persistenceInterface->getUsersRolesInStudy($getUserRolesRequest->userId, $getUserRolesRequest->study);
            }

            $getUserRoleResponse->body = $roles;
            $getUserRoleResponse->status = 200;
            $getUserRoleResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $getUserRoleResponse->body = $e->getErrorBody();
            $getUserRoleResponse->status = $e->statusCode;
            $getUserRoleResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId, int $userId, ?string $studyName){

        if( empty($studyName) ){
            //Get User's Role accross all studies, only for administrators
            $this->authorizationService->setCurrentUser($userId);
            if( ! $this->authorizationService->isAdmin($userId)) {
                throw new GaelOForbiddenException();
            };
        }else{
            //Get user's roles in study, only for user itself
            return $currentUserId === $userId;

        }

    }
}

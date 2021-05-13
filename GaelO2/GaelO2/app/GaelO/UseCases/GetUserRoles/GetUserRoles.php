<?php

namespace App\GaelO\UseCases\GetUserRoles;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetUserRoles {

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetUserRolesRequest $getUserRolesRequest, GetUserRolesResponse $getUserRoleResponse) : void {

        try{

            $this->checkAuthorization($getUserRolesRequest->currentUserId);
            $roles = $this->userRepositoryInterface->getUsersRoles($getUserRolesRequest->userId);

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

    private function checkAuthorization(int $currentUserId){
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        $admin = $this->authorizationService->isAdmin();
        if( !$admin ) {
            throw new GaelOForbiddenException();
        }

    }
}

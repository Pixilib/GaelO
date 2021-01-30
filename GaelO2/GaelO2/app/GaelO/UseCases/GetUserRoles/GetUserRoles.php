<?php

namespace App\GaelO\UseCases\GetUserRoles;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\UserRepositoryInterface;
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

            $this->checkAuthorization($getUserRolesRequest->currentUserId, $getUserRolesRequest->userId, $getUserRolesRequest->study);
            if( $getUserRolesRequest->study === null ){
                $roles = $this->userRepositoryInterface->getUsersRoles($getUserRolesRequest->userId);
            }else {
                $roles = $this->userRepositoryInterface->getUsersRolesInStudy($getUserRolesRequest->userId, $getUserRolesRequest->study);
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

    private function checkAuthorization(int $currentUserId, int $userId){
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        $admin = $this->authorizationService->isAdmin();

        //If not same userID and not admin privilege
        if( $currentUserId !== $userId && !$admin ) {
            throw new GaelOForbiddenException();
        }

    }
}

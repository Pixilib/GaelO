<?php

namespace App\GaelO\UseCases\DeleteUserRole;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class DeleteUserRole {

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface  = $trackerRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse) : void {

        try {

            $this->checkAuthorization($deleteUserRoleRequest->currentUserId);

            $study = $deleteUserRoleRequest->study;
            $role = $deleteUserRoleRequest->role;
            $userId = $deleteUserRoleRequest->userId;

            $this->userRepositoryInterface->deleteRoleForUser($userId, $study, $role);

            $actionDetails = [
                'deletedRole' => $role
            ];

            $this->trackerRepositoryInterface->writeAction($deleteUserRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $deleteUserRoleResponse->status = 200;
            $deleteUserRoleResponse->statusText = 'OK';

        }catch(GaelOException $e){
            $deleteUserRoleResponse->status = $e->statusCode;
            $deleteUserRoleResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $userId){
        $this->authorizationService->setCurrentUserAndRole($userId);
        if ( ! $this->authorizationService->isAdmin() ){
            throw new GaelOForbiddenException();
        };

    }
}

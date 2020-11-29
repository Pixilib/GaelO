<?php

namespace App\GaelO\UseCases\DeleteUserRole;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use Exception;

class DeleteUserRole {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService  = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse) : void {

        try {

            $this->checkAuthorization($deleteUserRoleRequest->currentUserId);

            $study = $deleteUserRoleRequest->study;
            $role = $deleteUserRoleRequest->role;
            $userId = $deleteUserRoleRequest->userId;

            $this->persistenceInterface->deleteRoleForUser($userId, $study, $role);

            $actionDetails = [
                'deletedRole' => $role
            ];

            $this->trackerService->writeAction($deleteUserRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

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

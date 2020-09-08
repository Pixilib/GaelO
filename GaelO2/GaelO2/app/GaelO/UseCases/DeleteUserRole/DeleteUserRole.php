<?php

namespace App\GaelO\UseCases\DeleteUserRole;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class DeleteUserRole {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService  = $trackerService;
    }

    public function execute(DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse) : void {

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

    }
}

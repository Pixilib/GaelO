<?php

namespace App\GaelO\UseCases\DeleteUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\TrackerService;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;

class DeleteUser {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService  = $trackerService;
        $this->authorizationService = $authorizationService;
    }

    public function execute(DeleteUserRequest $deleteRequest, DeleteUserResponse $deleteResponse) : void {

        $this->authorizationService->isAdmin($deleteRequest->currentUserId);

        $this->persistenceInterface->delete($deleteRequest->id);
        $deleteResponse->status = 200;
        $deleteResponse->statusText = 'OK';

        $actionsDetails = [
            'deactivated_user'=>$deleteRequest->id
        ];

        $this->trackerService->writeAction($deleteRequest->currentUserId,
                                Constants::TRACKER_ROLE_USER, null, null,
                                Constants::TRACKER_EDIT_USER, $actionsDetails);

    }

}

?>

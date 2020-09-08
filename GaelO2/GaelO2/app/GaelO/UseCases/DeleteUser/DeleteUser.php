<?php

namespace App\GaelO\UseCases\DeleteUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\Mails\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\UseCases\DeleteUser\DeleteUserRequest;
use App\GaelO\UseCases\DeleteUser\DeleteUserResponse;

class DeleteUser {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService  = $trackerService;
    }

    public function execute(DeleteUserRequest $deleteRequest, DeleteUserResponse $deleteResponse) : void {

        $currentUserDetails = $this->persistenceInterface->find($deleteRequest->currentUserId);
        $this->persistenceInterface->delete($deleteRequest->id);
        $deleteResponse->status = 200;
        $deleteResponse->statusText = 'OK';

        $actionsDetails = [
            'deactivated_user'=>$deleteRequest->id
        ];

        $this->trackerService->writeAction($currentUserDetails['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $actionsDetails);

    }

}

?>

<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\UserService;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface, AuthorizationService $authorizationService, TrackerService $trackerService, MailServices $mailService, UserService $userService){
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationService = $authorizationService;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
        $this->userService = $userService;
    }

    public function execute(ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse) : void {

        if ($this->checkAuthorization($modifyUserRequest->currentUserId)){

            $temporaryPassword = null;
            if($modifyUserRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
                $newPassword = substr(uniqid(), 1, 10);
                $temporaryPassword = LaravelFunctionAdapter::hash( $newPassword );
            }

            $this->userService->updateUser($modifyUserRequest, $temporaryPassword);

            if($modifyUserRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
                $this->mailService->sendResetPasswordMessage(
                    ($modifyUserRequest->firstname.' '.$modifyUserRequest->lastname),
                    $modifyUserRequest->username,
                    $newPassword,
                    $modifyUserRequest->email
                );
            }

            $this->writeInTracker($modifyUserRequest->userId, $modifyUserRequest->status);

            $modifyUserResponse->status = 200;
            $modifyUserResponse->statusText = 'OK';

        } else {
            $modifyUserResponse->status = 403;
            $modifyUserResponse->statusText = 'Forbidden';
        };


    }

    private function writeInTracker($userId, $status){
        $details = [
            'modified_user_id'=>$userId,
            'status'=>$status
        ];
        $this->trackerService->writeAction($userId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $details);

    }

    private function checkAuthorization($userId) : bool {
        return $this->authorizationService->isAdmin($userId);
    }
}

?>

<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\UserService;
use Exception;

class ModifyUser {

    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailService;
    private UserService $userService;

    public function __construct(AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailService, UserService $userService){
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->userService = $userService;
    }

    public function execute(ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse) : void {

         try{

            $this->checkAuthorization($modifyUserRequest->currentUserId);

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

            $details = [
                'modified_user_id'=>$modifyUserRequest->userId,
                'status'=>$modifyUserRequest->status
            ];

            $this->trackerRepositoryInterface->writeAction($modifyUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserResponse->status = 200;
            $modifyUserResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $modifyUserResponse->status = $e->statusCode;
            $modifyUserResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        };


    }

    private function checkAuthorization($userId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }
}

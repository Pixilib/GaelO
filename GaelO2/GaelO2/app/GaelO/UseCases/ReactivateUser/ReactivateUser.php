<?php

namespace App\GaelO\UseCases\ReactivateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use Exception;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;

class ReactivateUser{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailServices){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse){

        try{

            $this->checkAuthorization($reactivateUserRequest->currentUserId);

            //Undelete User
            $this->userRepositoryInterface->reactivateUser($reactivateUserRequest->userId);
            //Generate new password and set status unconfirmed
            $user = $this->userRepositoryInterface->find($reactivateUserRequest->userId);
            $newPassword = substr(uniqid(), 1, 10);

            $this->userRepositoryInterface->updateUserTemporaryPassword($user['id'], $newPassword);
            $this->userRepositoryInterface->updateUserStatus($user['id'], Constants::USER_STATUS_UNCONFIRMED);


            $this->mailServices->sendResetPasswordMessage(
                ($user['firstname'].' '.$user['lastname']),
                $user['username'],
                $newPassword,
                $user['email']
            );

            $actionsDetails = [
                'reactivatedUser' => $reactivateUserRequest->userId
            ];
            $this->trackerRepositoryInterface->writeAction($reactivateUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionsDetails);

            $reactivateUserResponse->status = 200;
            $reactivateUserResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $reactivateUserResponse->status = $e->statusCode;
            $reactivateUserResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization($userId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}

<?php

namespace App\GaelO\UseCases\ReactivateUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use Exception;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;

class ReactivateUser{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface, FrameworkInterface $frameworkInterface){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(ReactivateUserRequest $reactivateUserRequest, ReactivateUserResponse $reactivateUserResponse){

        try{

            $this->checkAuthorization($reactivateUserRequest->currentUserId);

            //Undelete User
            $this->userRepositoryInterface->reactivateUser($reactivateUserRequest->userId);
            //Generate new password and set status unconfirmed
            $user = $this->userRepositoryInterface->find($reactivateUserRequest->userId);

            $this->userRepositoryInterface->updateUser(
                $user['id'],
                $user['lastname'],
                $user['firstname'],
                $user['email'],
                $user['phone'],
                $user['administrator'],
                $user['center_code'],
                $user['job'],
                $user['orthanc_address'],
                $user['orthanc_login'],
                $user['orthanc_password'],
                true
            );

            //Send reset password link.
            $emailSendSuccess = $this->frameworkInterface->sendResetPasswordLink($user['email']);
            if (! $emailSendSuccess) throw new Exception('Error Sending Reset Email');

            $actionsDetails = [
                'reactivatedUser' => $reactivateUserRequest->userId
            ];
            $this->trackerRepositoryInterface->writeAction($reactivateUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionsDetails);

            $reactivateUserResponse->status = 200;
            $reactivateUserResponse->statusText = 'OK';

        } catch( GaelOException $e){

            $reactivateUserResponse->body = $e->getErrorBody();
            $reactivateUserResponse->status = $e->statusCode;
            $reactivateUserResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization($userId)  {
        $this->authorizationUserService->setUserId($userId);
        if( ! $this->authorizationUserService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}

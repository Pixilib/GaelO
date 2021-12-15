<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use Exception;

class ChangePassword {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
     }

    public function execute(ChangePasswordRequest $changeUserPasswordRequest, ChangePasswordResponse $changeUserPasswordResponse) : void {

        try {

            $id = $changeUserPasswordRequest->id;

            $user = $this->userRepositoryInterface->find($id);
            $this->userRepositoryInterface->updateUserStatus($user['id'], Constants::USER_STATUS_ACTIVATED);
            $this->trackerRepositoryInterface->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_CHANGE_PASSWORD, null);

            $changeUserPasswordResponse->status = 200;
            $changeUserPasswordResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $changeUserPasswordResponse->body = $e->getErrorBody();
            $changeUserPasswordResponse->status = $e->statusCode;
            $changeUserPasswordResponse->statusText = $e->statusText;
        } catch(Exception $e){
            throw $e;
        }

    }

}

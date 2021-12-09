<?php

namespace App\GaelO\UseCases\ResetPassword;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\UseCases\ResetPassword\ResetPasswordResponse;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Util;
use Exception;

class ResetPassword {

    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailServices;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, MailServices $mailServices, TrackerRepositoryInterface $trackerRepositoryInterface ){
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
     }

    public function execute(ResetPasswordRequest $resetPasswordRequest, ResetPasswordResponse $resetPasswordResponse) : void {
        try {
            $email = $resetPasswordRequest->email;

            $userEntity = $this->userRepositoryInterface->getUserByEmail($email, true);

            $this->checkNotDeactivatedAccount($userEntity);
            $this->checkEmailMatching($email, $userEntity['email']);

            //update user data
            $this->userRepositoryInterface->updateUserStatus($userEntity['id'], Constants::USER_STATUS_UNCONFIRMED);
            FrameworkAdapter::resendVerificationEmail($userEntity['id']);
            $this->userRepositoryInterface->updateUserAttempts($userEntity['id'], 0);

            //Write action in tracker
            $this->trackerRepositoryInterface->writeAction($userEntity['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_RESET_PASSWORD, null);

            $resetPasswordResponse->status = 200;
            $resetPasswordResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $resetPasswordResponse->status = $e->statusCode;
            $resetPasswordResponse->statusText = $e->statusText;
            $resetPasswordResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkEmailMatching($inputEmail, $databaseEmail){
        if($inputEmail !== $databaseEmail) throw new GaelOBadRequestException('Incorrect Email');
    }

    private function checkNotDeactivatedAccount(array $user){
        if($user['deleted_at'] !== null) {
            //Send Email change password failure
            $this->mailServices->sendForbiddenResetPasswordDueToDeactivatedAccount($user['email'],
                    $user['lastname'], $user['firstname']);
            throw new GaelOBadRequestException('Deactivated Account');
        }
    }
}

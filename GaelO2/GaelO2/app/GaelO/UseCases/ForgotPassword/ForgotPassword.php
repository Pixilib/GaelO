<?php

namespace App\GaelO\UseCases\ForgotPassword;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\MailServices;
use Exception;

class ForgotPassword
{

    private FrameworkInterface $frameworkInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailServices;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct( FrameworkInterface $frameworkInterface, UserRepositoryInterface $userRepositoryInterface, MailServices $mailServices, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->frameworkInterface = $frameworkInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailServices = $mailServices;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ForgotPasswordRequest $forgotPasswordRequest, ForgotPasswordResponse $forgotPasswordResponse): void
    {
        try {
            $email = $forgotPasswordRequest->email;

            $userEntity = $this->userRepositoryInterface->getUserByEmail($email, true);

            $this->checkNotDeactivatedAccount($userEntity);

            $this->userRepositoryInterface->updateUserAttempts($userEntity['id'], 0);

            $emailSendSuccess = $this->frameworkInterface->sendResetPasswordLink($email);

            if (! $emailSendSuccess) throw new Exception('Error Sending Reset Email');

            //Write action in tracker
            $this->trackerRepositoryInterface->writeAction($userEntity['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_RESET_PASSWORD, null);

            $forgotPasswordResponse->status = 200;
            $forgotPasswordResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $forgotPasswordResponse->status = $e->statusCode;
            $forgotPasswordResponse->statusText = $e->statusText;
            $forgotPasswordResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkNotDeactivatedAccount(array $user)
    {
        if ($user['deleted_at'] !== null) {
            //Send Email change password failure
            $this->mailServices->sendForbiddenResetPasswordDueToDeactivatedAccount(
                $user['email'],
                $user['lastname'],
                $user['firstname']
            );
            throw new GaelOBadRequestException('Deactivated Account');
        }
    }
}
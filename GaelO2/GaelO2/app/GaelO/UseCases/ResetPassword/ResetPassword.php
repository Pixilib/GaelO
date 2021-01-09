<?php

namespace App\GaelO\UseCases\ResetPassword;

use App\GaelO\Util;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\UseCases\ResetPassword\ResetPasswordResponse;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use Exception;

class ResetPassword {

    public function __construct(PersistenceInterface $persistenceInterface, MailServices $mailServices, TrackerService $trackerService ){
        $this->persistenceInterface = $persistenceInterface;
        $this->mailServices = $mailServices;
        $this->trackerService = $trackerService;
     }

    public function execute(ResetPasswordRequest $resetPasswordRequest, ResetPasswordResponse $resetPasswordResponse) : void {
        try {
            $username = $resetPasswordRequest->username;
            $email = $resetPasswordRequest->email;

            $userEntity = $this->persistenceInterface->getUserByUsername($username, true);

            $this->checkNotDeactivatedAccount($userEntity);
            $this->checkEmailMatching($email, $userEntity['email']);

            //update properties of user
            $userEntity['status'] = Constants::USER_STATUS_UNCONFIRMED;
            $newPassword = substr(uniqid(), 1, 10);
            $userEntity['password_temporary'] = LaravelFunctionAdapter::hash( $newPassword );
            $userEntity['attempts'] = 0;
            $userEntity['last_password_update'] = Util::now();
            //update user
            $this->persistenceInterface->update($userEntity['id'], $userEntity);
            //send email
            $this->mailServices->sendResetPasswordMessage(
                ($userEntity['firstname'].' '.$userEntity['lastname']),
                $userEntity['username'],
                $newPassword,
                $userEntity['email']);
            //Write action in tracker
            $this->trackerService->writeAction($userEntity['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_RESET_PASSWORD, null);

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
                    $user['username']);
            throw new GaelOBadRequestException('Deactivated Account');
        }
    }
}

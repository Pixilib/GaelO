<?php

namespace App\GaelO\UseCases\ResetPassword;

use App\GaelO\Util;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\UseCases\ResetPassword\ResetPasswordResponse;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\Mails\MailServices;

class ResetPassword {

    public function __construct(PersistenceInterface $persistenceInterface, MailServices $mailServices ){
        $this->persistenceInterface = $persistenceInterface;
        $this->mailServices = $mailServices;
     }

    public function execute(ResetPasswordRequest $resetPasswordRequest, ResetPasswordResponse $resetPasswordResponse) : void {
        $username = $resetPasswordRequest->username;
        $email = $resetPasswordRequest->email;
        try{
            $userEntity = $this->persistenceInterface->getUserByUsername($username);
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
            $emailsParameters = ['username' => $userEntity['username'], 'email'=>$userEntity['email'] , 'newPassword' => $newPassword];
            $this->mailServices->sendResetPasswordMessage($emailsParameters);
            //SK TODO tracker

        } catch (GaelOException $e){
            $resetPasswordResponse->status = 500;
            $resetPasswordResponse->statusText = $e->getMessage();
        }



    }

    private function checkEmailMatching($inputEmail, $databaseEmail){
        if($inputEmail !== $databaseEmail) throw new GaelOException('Incorrect Email');
    }
}

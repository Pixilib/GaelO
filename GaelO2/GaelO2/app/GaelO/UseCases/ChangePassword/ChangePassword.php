<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Util;
use App\GaelO\Constants\Constants;

class ChangePassword {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

     public function execute(ChangePasswordRequest $userRequest, ChangePasswordResponse $userResponse) : void {
        $username = $userRequest->username; 
        $previousPassword = $userRequest->previous_password;
        $password1 = $userRequest->password1;
        $password2 = $userRequest->password2;
        //SK : Ici tu devrait faire venir l'ID depuis la route non ?
        $user = $this->persistenceInterface->getUserByUsername($username);

        try{
            $this->checkNewPassword($password1, $user['password_temporary'] , $user['password_previous1'],  $user['password_previous2']);
            $this->checkMatchPasswords($password1, $password2, false);
            $this->checkPasswordFormatCorrect($password1);

            if($user['status'] == Constants.USER_STATUS_UNCONFIRMED) {
                $this->checkMatchPasswords($previousPassword, $user['password_temporary'], true);
            } else {
                $this->checkMatchPasswords($previousPassword, $user['password'], true);
            }

        }catch(Throwable $t) {
            //SK ICI on devrait definir nos execption à nous pour pouvoir output nos exeception message et pas celles qui viennent du framework
            $userResponse->status = 500;
            $userResponse->statusText = $t->getMessage();
            
        }

        $data['password_previous1'] = $user['password'];
        $data['password_previous2'] = $user['password_previous1'];
        $data['password'] = LaravelFunctionAdapter::hash($password1);
        $data['last_password_update'] = Util::now();
        $data['status'] = 'Activated';
        
        $this->persistenceInterface->update($user['id'], $data);
    
        //+ Tracker log
        

        

     }

    private function checkNewPassword($passwordCandidate, $temporaryPassword, $previousPassword1, $previousPassword2){
        if( $passwordCandidate == $temporaryPassword || $passwordCandidate == $previousPassword1 || $passwordCandidate == $previousPassword2 ){
            throw new Exception('Already Previously Used Password');
        }
    }

    private function checkPasswordFormatCorrect(string $password) {
        if ( strlen($password) < 8 || !preg_match('/[^a-z0-9]/i', $password) || strtolower($password != $password) ){
            throw new Exception('Password Contraints Failure');
        }
    }

    private function checkMatchPasswords(string $pass1, string $pass2, bool $currentPasswordCheck) {
        if( $pass1 != $pass2 ) {
            if ($currentPasswordCheck) throw new Exception('Not Matching Current Password');
            else  throw new Exception('Not Matching Previous Password');
        }
    }
  
}

?>
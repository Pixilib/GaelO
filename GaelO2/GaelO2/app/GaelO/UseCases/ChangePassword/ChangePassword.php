<?php

namespace App\GaelO\UseCases\ChangePassword;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ChangePassword\ChangePasswordRequest;
use App\GaelO\UseCases\ChangePassword\ChangePasswordResponse;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Util;
use App\GaelO\Constants\Constants;

use App\GaelO\Exceptions\GaelOException;

class ChangePassword {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

    public function execute(ChangePasswordRequest $userRequest, ChangePasswordResponse $userResponse) : void {
        $id = $userRequest->id;
        $username = $userRequest->username; 
        $previousPassword = $userRequest->previous_password;
        $password1 = $userRequest->password1;
        $password2 = $userRequest->password2;

        $user = $this->persistenceInterface->find($id);

        try{

            if($user['status'] == Constants::USER_STATUS_UNCONFIRMED) {
                $this->checkMatchPasswords($previousPassword, $user['password_temporary'], true);
            } else {
                $this->checkMatchPasswords($previousPassword, $user['password'], true);
            }

            $this->checkPasswordFormatCorrect($password1);
            $this->checkMatchPasswords($password1, $password2, false);
            $this->checkNewPassword( LaravelFunctionAdapter::hash($password1), $user['password_temporary'] , $user['password'], $user['password_previous1'], $user['password_previous2']);

        }catch(GaelOException $e) {
            $userResponse->status = 400;
            $userResponse->statusText = $e->getMessage();
            return;
        }

        $data['password_previous1'] = $user['password'];
        $data['password_previous2'] = $user['password_previous1'];
        $data['password'] = LaravelFunctionAdapter::hash($password1);
        $data['last_password_update'] = Util::now();
        $data['status'] = Constants::USER_STATUS_ACTIVATED;
        
        $this->persistenceInterface->update($user['id'], $data);
    
        //+ Tracker log => A faire
        //Tracker::logActivity($username, "User", null, null, "Change Password", "Password Changed");

    }

     /**
      * Check that candidate password is not in the 3 last used passwords
      */
    private function checkNewPassword($passwordCandidate, $temporaryPassword, $currentPassword, $previousPassword1, $previousPassword2) : void {
        if( $passwordCandidate == $temporaryPassword || 
                $passwordCandidate == $currentPassword ||
                $passwordCandidate == $previousPassword1 || 
                $passwordCandidate == $previousPassword2 ) {
            throw new GaelOException('Already Previously Used Password');
        }
    }

    /**
     * Check Password constraints : 
     * Should have length at least 8 carachters
     * Should not have carachters different from alfa numerical
     * Should have at least a differente case (so strlower should not be equal to original string)
     */
    private function checkPasswordFormatCorrect(string $password) {
        if ( strlen($password) < 8 || preg_match('/[^a-z0-9]/i', $password) || strtolower($password) == $password ){
            throw new GaelOException('Password Contraints Failure');
        }
    }

    /**
     * Check password equality, used to check current password and new candidate password
     */
    private function checkMatchPasswords(string $pass1, string $pass2, bool $currentPasswordCheck) : void {
        if( $pass1 != $pass2 ) {
            if ($currentPasswordCheck) throw new Exception('Not Matching Current Password');
            else  throw new GaelOException('Not Matching New Password');
        }
    }
  
}

?>
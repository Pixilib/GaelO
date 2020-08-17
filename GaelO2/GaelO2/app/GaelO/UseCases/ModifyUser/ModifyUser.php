<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Util;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

     public function changeUserPassword(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void {
        $username = $userRequest->username; 
        $previousPassword = $userRequest->previous_password;
        $password1 = $userRequest->password1;
        $password2 = $userRequest->password2;

        if($password1 !== $password2) {
            throw new Exception('Different passwords');
        }

        $user = $this->persistenceInterface->getUserByUsername($username);

        if($user['status'] == 'Unconfirmed') {
            $checkCurrentPassword = $this->checkMatchPasswords($previousPassword, $user['password_temporary']);
        } else {
            $checkCurrentPassword = $this->checkMatchPasswords($previousPassword, $user['password']);
        }

        if(!$checkCurrentPassword) {
            throw new Exception('Wrong old password');
        }

        if(!$this->checkPasswordFormat($password1)){
            throw new Exception('Incorrect Format');
        }

        if($password1 == $user['password_temporary'] || $password1 == $user['password_previous1'] || $password1 == $user['password_previous2']){
            throw new Exception('Match previous password');
        }

        $data['password_previous1'] = $user['password'];
        $data['password_previous2'] = $user['password_previous1'];
        $data['password'] = LaravelFunctionAdapter::hash($password1);
        $data['last_password_update'] = Util::now();
        $data['status'] = 'Activated';
        
        $this->persistenceInterface->update($user['id'], $data);
        
        //+ Tracker log
        $userResponse->success = true;

     }

     public function modifyUser(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {   
        $username = $userRequest->username;
        $user->getUserByUsername($username);
        echo $user;
        $userResponse->username = $userRequest->username;
        if($username == true) $userResponse->success = true;
    }

    private function checkPasswordFormat(string $password) {
        return (strlen($password) < 8 || preg_match('/[^a-z0-9]/i', $password) || strtolower($password) == $password);
    }

    private function checkMatchPasswords(string $pass1, string $pass2) {
        return $pass1 = $pass2;
    }
  
}

?>
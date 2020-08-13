<?php

namespace App\GaelO\ModifyUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\ModifyUser\ModifyUserRequest;
use App\GaelO\ModifyUser\ModifyUserResponse;


class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

     public function changePassword(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void {
         $password = $userRequest->$password;
         
     }

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {   
        $username = $userRequest->username;
        $userResponse->username = $userRequest->username;
        if($username == true) $userResponse->success = true;
    }
  
}

?>
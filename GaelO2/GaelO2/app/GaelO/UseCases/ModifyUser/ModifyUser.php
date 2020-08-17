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

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {   
        $username = $userRequest->username;
        try {        
            $user->getUserByUsername($username);
            $userResponse->status = 200;
            $userResponse->body = 'User modified';
            $userResponse->statusText = 'OK';  
        } catch (\Throwable $t) {
            $userResponse->status = 500;            
        } 
    }

}

?>
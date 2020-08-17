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
        $user->getUserByUsername($username);
        echo $user;
        $userResponse->username = $userRequest->username;
        if($username == true) $userResponse->status = 200;
    }

}

?>
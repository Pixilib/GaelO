<?php

namespace App\GaelO\GetUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\GetUser\GetUserRequest;
use App\GaelO\GetUser\GetUserResponse;


class GetUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

    public function get(GetUserRequest $userRequest, GetUserResponse $userResponse) : void
    {   
        $username = $userRequest->username;
        $userResponse->username = $userRequest->username;
        if($username == true) $userResponse->success = true;
    }
  
}

?>
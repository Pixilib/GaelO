<?php

namespace App\GaelO\UseCases\GetUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;


class GetUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 

    public function get(GetUserRequest $userRequest, GetUserResponse $userResponse) : void
    {   
        $id = $userRequest->id;
        $userResponse->user = $this->persistenceInterface->find($id);
        if($id == true) $userResponse->success = true;
    }
  
    public function getAllUsers(GetUserRequest $userRequest, GetUserResponse $userResponse) 
    {
        $userResponse->users = $this->persistenceInterface->getAllUsers();
        $userResponse->success = true;
    }
}

?>
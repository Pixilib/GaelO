<?php

namespace App\GaelO\CreateUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\CreateUser\CreateUserRequest;
use App\GaelO\CreateUser\CreateUserResponse;


class CreateUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     } 
  
     public function someLogicMethod($id){
        // do something and save state to the gateway
        $this->persistenceInterface->saveData($id, ['some_state'=>'value']);
     }
  
     public function someDataReturnMethod($id){
        return $this->persistenceInterface->retrieveData($id);
     }

    //logique métier (ex validation ...)
     public function execute(CreateUserRequest $userRequest, CreateUserResponse $userResponse) : void
    {   
        $username = $userRequest->username;
        $userResponse->username = $userRequest->username;
        if($username == true) $userResponse->success = true;
    }
  
}

?>
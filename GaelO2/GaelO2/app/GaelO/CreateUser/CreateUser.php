<?php

namespace App\GaelO\CreateUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\CreateUser\CreateUserRequest;
use App\GaelO\CreateUser\CreateUserResponse;

use App\GaelO\Util;

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
     public function createUser(CreateUserRequest $userRequest, CreateUserResponse $userResponse) : void
    {   
        $newUser = $userRequest;
        $data = get_object_vars($userRequest);

        
        //Check on fields (password length...)

        $this->persistenceInterface->createUser($data);
        if($username == true) $userResponse->success = true;
    }

    private function validatePassword(string $password) {

    }
  
}

?>
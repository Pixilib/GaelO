<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;

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
        $data = get_object_vars($userRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $data['password_temporary'] = $password;
        if(isset($data['administrator'])) $data['administrator'] = true;
        
        //Let only numbers for phone number
        $data['phone']=preg_replace("/[^0-9]/", "", $data['phone']);
        //Check form completion
        if(!isset($data['username']) || !isset($data['last_name']) || !isset($data['email']) || !is_numeric($data['center'])) {
            throw new Exception('Form incomplete');
        } else if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) {
            throw new Exception('Not a valid email format');
        } else {
            //Data are ok to be written in db        
            $this->persistenceInterface->createUser($data);

            //ADD LOG + MAIL CONFIRMATION
            
            $userResponse->success = true;
        }

        //Check on fields (password length...)

        
    }

    private function validatePassword(string $password) {

    }
  
}

?>
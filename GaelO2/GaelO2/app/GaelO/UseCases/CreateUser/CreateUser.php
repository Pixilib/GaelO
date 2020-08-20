<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Exceptions\GaelOException;
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
     public function execute(CreateUserRequest $userRequest, CreateUserResponse $userResponse) : void
    {
        $data = get_object_vars($userRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $data['password_temporary'] = $password;
        $data['password'] = $password;
        $data['creation_date'] = Util::now();
        $data['last_password_update'] = Util::now();
        if(isset($data['administrator'])) $data['administrator'] = true;

        //Let only numbers for phone number
        if (isset($data['phone'])) $data['phone']=preg_replace("/[^0-9]/", "", $data['phone']);
        //Check form completion
        if(!isset($data['username']) || !isset($data['lastname']) || !isset($data['email']) || !is_numeric($data['center_code'])) {
            throw new GaelOException('Form incomplete');
        } else if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) {
            throw new GaelOException('Not a valid email format');
        } else {
            //Data are ok to be written in db
            try {
                $this->persistenceInterface->create($data);
                $userResponse->status = 201;
                $userResponse->statusText = 'Created';
            } catch (\Throwable $t) {
                error_log(print_r($t->getMessage()));
                $userResponse->status = 500;
            }
            //ADD LOG + MAIL CONFIRMATION



        }

        //Check on fields (password length...)


    }

    private function validatePassword(string $password) {

    }

}

?>

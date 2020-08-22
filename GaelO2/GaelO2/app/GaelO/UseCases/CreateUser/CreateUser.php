<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Util;

class CreateUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

     public function execute(CreateUserRequest $userRequest, CreateUserResponse $userResponse) : void
    {
        $data = get_object_vars($userRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $data['password_temporary'] = LaravelFunctionAdapter::Hash($password);
        $data['creation_date'] = Util::now();
        $data['last_password_update'] = Util::now();
        //SK A quoi sert cette ligne ?, le boolean devrait deja venir dans le DTO Request
        if(isset($data['administrator'])) $data['administrator'] = true;

        //Let only numbers for phone number
        if (isset($data['phone'])) $data['phone']=preg_replace("/[^0-9]/", "", $data['phone']);

        //Check form completion
        try {
            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            $this->checkUserUnique($data);
            //Data are ok to be written in db
            $this->persistenceInterface->create($data);
            $userResponse->status = 201;
            $userResponse->statusText = 'Created';
            //ADD LOG + MAIL CONFIRMATION

        } catch (GaelOException $e) {
            $userResponse->status = 500;
            $userResponse->statusText = $e->getMessage();
        }catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkFormComplete(array $data) : void {
        if(!isset($data['username']) || !isset($data['lastname']) || !isset($data['email']) || !is_numeric($data['center_code'])) {
            throw new GaelOException('Form incomplete');
        }
    }

    private function checkEmailValid(array $data) : void {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) {
            throw new GaelOException('Not a valid email format');
        }

    }

    private function checkUserUnique(array $data) : void {
        $getUsers = $this->persistenceInterface->getAll();
        foreach($getUsers as $index => $users) {
            foreach($users as $property => $value) {
                if ($data['username'] == $users['username']) throw new GaelOException('Username already taken');
                if ($data['email'] == $users['email']) throw new GaelOException('Email already taken');
            }

        }
    }
}

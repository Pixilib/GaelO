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
        //SK Plutot que de passer en array des le debut pourquoi ne pas checker avec les clé directement ?
        $data = get_object_vars($userRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $data['password_temporary'] = LaravelFunctionAdapter::Hash($password);
        //Set copy as password to fill databse constraint (password not null)
        $data['password'] = LaravelFunctionAdapter::Hash($password);
        $data['creation_date'] = Util::now();
        $data['last_password_update'] = Util::now();
        //SK A quoi sert cette ligne ?, le boolean devrait deja venir dans le DTO Request
        if(isset($data['administrator'])) $data['administrator'] = true;

        //Check form completion
        try {
            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            $this->checkUserUnique($data);
            $this->checkPhoneCorrect($data['phone']);
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

    private function checkPhoneCorrect(string $phone) : void {
        //If contains non number caracters throw error
        if (preg_match('/[^0-9]/', $phone)) {
            throw new GaelOException('Not a valid email phone number');
        }
    }

    private function checkUserUnique(array $data) : void {
        //SK ici faire des methode pour checker qu'il n'y a pas les clé,
        //Ne pas lister toute la table
        $getUsers = $this->persistenceInterface->getAll();
        foreach($getUsers as $index => $users) {
            foreach($users as $property => $value) {
                if ($data['username'] == $users['username']) throw new GaelOException('Username already taken');
                if ($data['email'] == $users['email']) throw new GaelOException('Email already taken');
            }

        }
    }
}

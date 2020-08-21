<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Util;
use App;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
     }

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {
        //TODO
        $id = $userRequest->id;
        $data = get_object_vars($userRequest);
        $user = $this->persistenceInterface->find($id);

        try {
            $this->persistenceInterface->update($user['id'], $data);
            $userResponse->status = 200;
            $userResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $userResponse->status = 500;
            $userResponse->statusText = $e->getMessage();
        }
    }

    private function isFormComplete(array $data) {
        if(!isset($data['username']) || !isset($data['lastname']) || !isset($data['email']) || !is_numeric($data['center_code'])) throw new GaelOException('Form incomplete');
        return true;
    }

    private function isEmailValid(array $data) {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) throw new GaelOException('Not a valid email format');
        return true;
    }

    private function isUserUnique(array $data) {
        $getUsers = $this->persistenceInterface->getAll();
        foreach($getUsers as $index => $users) {
            foreach($users as $property => $value) {
                if ($data['username'] == $users['username']) throw new GaelOException('Username already taken');
                if ($data['email'] == $users['email']) throw new GaelOException('Email already taken');
            }

        }
        return true;
    }

}

?>

<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Exceptions\GaelOException;

use App\GaelO\Services\TrackerService;

class ModifyUser {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
     }

     public function execute(ModifyUserRequest $userRequest, ModifyUserResponse $userResponse) : void
    {

        try {

            $id = $userRequest->id;
            $data = get_object_vars($userRequest);
            $user = $this->persistenceInterface->find($id);

            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            if($userRequest->email !== $user['email']) $this->checkNewEmailUnique($data['email']);
            if($userRequest->username !== $user['username']) $this->checkNewUsernameUnique($data['username']);

            $this->persistenceInterface->update($user['id'], $data);

            $userResponse->status = 200;
            $userResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $userResponse->status = 400;
            $userResponse->statusText = $e->getMessage();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkFormComplete(array $data) {
         if(!isset($data['username']) ||
        !isset($data['lastname']) ||
        !isset($data['firstname']) ||
        !isset($data['email']) ||
        !is_numeric($data['center_code']) ||
        !isset($data['job']) ||
        !isset($data['status']) ||
        !isset($data['administrator']) ||
        !isset($data['phone']) ||
        !isset($data['orthanc_address']) ||
        !isset($data['orthanc_login']) ||
        !isset($data['orthanc_password'])
        ) throw new GaelOException('Form incomplete');
    }

    private function checkEmailValid(array $data) {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $data['email'])) throw new GaelOException('Not a valid email format');
    }

    private function checkNewUsernameUnique($username){
        $user = $this->persistenceInterface->getUserByUsername($username);
        if(!empty($user)) throw new GaelOException("Username Already Used");

    }

    private function checkNewEmailUnique($email){
        $user = $this->persistenceInterface->getUserByEmail($email);
        if(!empty($user)) throw new GaelOException("Email Already Known");

    }

}

?>

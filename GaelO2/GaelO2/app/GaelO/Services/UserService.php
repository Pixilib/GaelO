<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationRequest;
use App\GaelO\Util;

class UserService
{
    public function __construct(UserRepository $persistenceInterface)
    {
        $this->persistenceInterface = $persistenceInterface;
    }

    public function createUser(CreateUserRequest $createUserRequest, string $passwordTemporary) : array {
        $password = null;
        $creationDate = Util::now();
        $lastPasswordUpdate = null;
        //Check form completion
        $this->checkFormComplete($createUserRequest);
        $this->checkEmailValid($createUserRequest->email);
        $this->checkUsernameUnique($createUserRequest->username);
        $this->checkEmailUnique($createUserRequest->email);
        $this->checkPhoneCorrect($createUserRequest->phone);

        //In no Exception thrown by checks methods, user are ok to be written in db
        $createdUserEntity = $this->persistenceInterface->createUser($createUserRequest->username,
                            $createUserRequest->lastname,
                            $createUserRequest->firstname,
                            Constants::USER_STATUS_UNCONFIRMED,
                            $createUserRequest->email,
                            $createUserRequest->phone,
                            $createUserRequest->administrator,
                            $createUserRequest->centerCode,
                            $createUserRequest->job,
                            $createUserRequest->orthancAddress,
                            $createUserRequest->orthancLogin,
                            $createUserRequest->orthancPassword,
                            $passwordTemporary,
                            $password,
                            $creationDate,
                            $lastPasswordUpdate);

        return $createdUserEntity;

    }

    public function updateUser(ModifyUserRequest $modifyUserRequest, ?string $temporaryPassword) : void {
        $user = $this->persistenceInterface->find($modifyUserRequest->userId);

        $this->checkFormComplete($modifyUserRequest);
        $this->checkEmailValid($modifyUserRequest->email);
        if($modifyUserRequest->email !== $user['email']) $this->checkEmailUnique($modifyUserRequest->email);
        if($modifyUserRequest->username !== $user['username']) $this->checkUsernameUnique($modifyUserRequest->username);


        //These property can't be modified in user edition
        $passwordTemporary = $temporaryPassword == null ? $user['password_temporary'] : $temporaryPassword;
        $modifyUserRequest->password = $user['password'];
        $modifyUserRequest->password_previous1 = $user['password_previous1'];
        $modifyUserRequest->password_previous2 = $user['password_previous2'];
        $modifyUserRequest->last_password_update = $user['last_password_update'];
        $modifyUserRequest->creation_date = $user['creation_date'];

        $this->persistenceInterface->updateUser($user['id'], $modifyUserRequest->username,
                                                            $modifyUserRequest->lastname,
                                                            $modifyUserRequest->firstname,
                                                            $modifyUserRequest->status,
                                                            $modifyUserRequest->email,
                                                            $modifyUserRequest->phone,
                                                            $modifyUserRequest->administrator,
                                                            $modifyUserRequest->centerCode,
                                                            $modifyUserRequest->job,
                                                            $modifyUserRequest->orthancAddress,
                                                            $modifyUserRequest->orthancLogin,
                                                            $modifyUserRequest->orthancPassword,
                                                            $passwordTemporary,
                                                            $modifyUserRequest->password,
                                                            $modifyUserRequest->creation_date,
                                                            $modifyUserRequest->last_password_update);
    }

    public function patchUser(ModifyUserIdentificationRequest $modifyUserIdentificationRequest) {
        $user = $this->persistenceInterface->find($modifyUserIdentificationRequest->userId);

        $this->checkEmailValid($modifyUserIdentificationRequest->email);
        $this->checkUsernameUnique($modifyUserIdentificationRequest->username);

        $this->persistenceInterface->updateUser($user['id'], $modifyUserIdentificationRequest->username,
                                                            $modifyUserIdentificationRequest->lastname,
                                                            $modifyUserIdentificationRequest->firstname,
                                                            $user['status'],
                                                            $modifyUserIdentificationRequest->email,
                                                            $modifyUserIdentificationRequest->phone,
                                                            $user['administrator'],
                                                            $user['centerCode'],
                                                            $user['job'],
                                                            $user['orthancAddress'],
                                                            $user['orthancLogin'],
                                                            $user['orthancPassword'],
                                                            $user['password_temporary'],
                                                            $user['password'],
                                                            $user['creation_date'],
                                                            $user['last_password_update']);
    }

    /**
     * PHP 8 change to Union type CreateUserRequest|ModifyUserRequest
     */
    private function checkFormComplete($userRequest) : void {
        if(!isset($userRequest->username)
        || !isset($userRequest->job)
        || !isset($userRequest->email)
        || !is_numeric($userRequest->centerCode)
        || !isset($userRequest->administrator) ) {
            throw new GaelOException('Form incomplete');
        }
    }

    private function checkEmailValid(string $email) : void {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $email)) {
            throw new GaelOException('Not a valid email format');
        }

    }

    private function checkPhoneCorrect(?string $phone) : void {
        //If contains non number caracters throw error
        if ($phone != null && preg_match('/[^0-9]/', $phone)) {
            throw new GaelOException('Not a valid email phone number');
        }
    }

    private function checkUsernameUnique(string $username) : void {
        $knownUsername = $this->persistenceInterface->isExistingUsername($username);
        if($knownUsername) throw new GaelOException("Username Already Used");

    }

    private function checkEmailUnique(string $email) : void {
        $knownEmail = $this->persistenceInterface->isExistingEmail($email);
        if($knownEmail) throw new GaelOException("Email Already Known");

    }
}

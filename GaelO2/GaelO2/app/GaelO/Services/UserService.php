<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationRequest;
use App\GaelO\Util;

class UserService
{
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function createUser(CreateUserRequest $createUserRequest, string $passwordTemporary) : array {
        $password = null;
        $creationDate = Util::now();
        //Check form completion
        $this->checkFormComplete($createUserRequest);
        $this->checkEmailValid($createUserRequest->email);
        $this->checkUsernameUnique($createUserRequest->username);
        $this->checkEmailUnique($createUserRequest->email);
        $this->checkPhoneCorrect($createUserRequest->phone);

        //In no Exception thrown by checks methods, user are ok to be written in db
        $createdUserEntity = $this->userRepositoryInterface->createUser($createUserRequest->username,
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
                            $creationDate);

        return $createdUserEntity;

    }

    public function updateUser(ModifyUserRequest $modifyUserRequest, ?string $temporaryPassword) : void {
        $user = $this->userRepositoryInterface->find($modifyUserRequest->userId);

        $this->checkFormComplete($modifyUserRequest);
        $this->checkEmailValid($modifyUserRequest->email);
        if($modifyUserRequest->email !== $user['email']) $this->checkEmailUnique($modifyUserRequest->email);
        if($modifyUserRequest->username !== $user['username']) $this->checkUsernameUnique($modifyUserRequest->username);


        //These property can't be modified in user edition
        $passwordTemporary = $temporaryPassword == null ? $user['password_temporary'] : $temporaryPassword;

        $this->userRepositoryInterface->updateUser($user['id'], $modifyUserRequest->username,
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
                                                            $passwordTemporary);
    }

    public function patchUser(ModifyUserIdentificationRequest $modifyUserIdentificationRequest) {
        $user = $this->userRepositoryInterface->find($modifyUserIdentificationRequest->userId);

        if($modifyUserIdentificationRequest->email !== $user['email']) {
            $this->checkEmailValid($modifyUserIdentificationRequest->email);
            $this->checkEmailUnique($modifyUserIdentificationRequest->email);
        }
        if($modifyUserIdentificationRequest->username !== $user['username']) $this->checkUsernameUnique($modifyUserIdentificationRequest->username);

        $this->userRepositoryInterface->updateUser($user['id'], $modifyUserIdentificationRequest->username,
                                                            $modifyUserIdentificationRequest->lastname,
                                                            $modifyUserIdentificationRequest->firstname,
                                                            $user['status'],
                                                            $modifyUserIdentificationRequest->email,
                                                            $modifyUserIdentificationRequest->phone,
                                                            $user['administrator'],
                                                            $user['center_code'],
                                                            $user['job'],
                                                            $user['orthanc_address'],
                                                            $user['orthanc_login'],
                                                            $user['orthanc_password'],
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
            throw new GaelOBadRequestException('Form incomplete');
        }
    }

    private function checkEmailValid(string $email) : void {
        if (!preg_match('/^[a-z0-9\-_.]+@[a-z0-9\-_.]+\.[a-z]{2,4}$/i', $email)) {
            throw new GaelOBadRequestException('Not a valid email format');
        }

    }

    private function checkPhoneCorrect(?string $phone) : void {
        //If contains non number caracters throw error
        if ($phone != null && preg_match('/[^0-9]/', $phone)) {
            throw new GaelOBadRequestException('Not a valid email phone number');
        }
    }

    private function checkUsernameUnique(string $username) : void {
        $knownUsername = $this->userRepositoryInterface->isExistingUsername($username);
        if($knownUsername) throw new GaelOConflictException("Username Already Used");

    }

    private function checkEmailUnique(string $email) : void {
        $knownEmail = $this->userRepositoryInterface->isExistingEmail($email);
        if($knownEmail) throw new GaelOConflictException("Email Already Known");

    }
}

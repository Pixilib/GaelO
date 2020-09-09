<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\Mails\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;

class CreateUser {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, MailServices $mailService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse) : void
    {
        $data = get_object_vars($createUserRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $passwordTemporary = LaravelFunctionAdapter::Hash($password);
        $password = null;
        $creationDate = Util::now();
        $lastPasswordUpdate = null;

        //Check form completion
        try {
            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            $this->checkUserUnique($data);
            $this->checkPhoneCorrect($data['phone']);

            //In no Exception thrown by check methods data are ok to be written in db
            $createdUserEntity = $this->persistenceInterface->createUser($createUserRequest->username,
                                $createUserRequest->lastname,
                                $createUserRequest->firstname,
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

            //save user creation in tracker
            $detailsTracker = [
                'id'=> $createdUserEntity['id']
            ];

            $this->trackerService->writeAction($createUserRequest->currentUserId,
                Constants::TRACKER_ROLE_USER,
                null,
                null,
                Constants::TRACKER_CREATE_USER,
                $detailsTracker);


            $this->mailService->sendCreatedAccountMessage($createdUserEntity['email'],
                                $createdUserEntity['firstname'].' '.$createdUserEntity['lastname'],
                                $createdUserEntity['username'],
                                $passwordTemporary);

            $createUserResponse->status = 201;
            $createUserResponse->statusText = 'Created';

        } catch (GaelOException $e) {
            $createUserResponse->status = 400;
            $createUserResponse->statusText = $e->getMessage();
        }catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkFormComplete(array $data) : void {
        if(!isset($data['username']) || !isset($data['lastname']) || !isset($data['email']) || !is_numeric($data['centerCode']) || !isset($data['administrator']) ) {
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
        if($this->persistenceInterface->isExistingEmail($data['email'])) throw new GaelOException('Already Existing Username');
        if($this->persistenceInterface->isExistingUsername($data['username'])) throw new GaelOException('Already used Email');
    }
}

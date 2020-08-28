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

     public function execute(CreateUserRequest $userRequest, CreateUserResponse $userResponse) : void
    {
        $data = get_object_vars($userRequest);
        //Generate password
        $password=substr(uniqid(), 1, 10);
        $data['password_temporary'] = LaravelFunctionAdapter::Hash($password);
        $data['password'] = null;
        $data['creation_date'] = Util::now();
        $data['last_password_update'] = null;
        //SK A quoi sert cette ligne ?, le boolean devrait deja venir dans le DTO Request
        if(isset($data['administrator'])) $data['administrator'] = true;

        //Check form completion
        try {
            $this->checkFormComplete($data);
            $this->checkEmailValid($data);
            $this->checkUserUnique($data);
            $this->checkPhoneCorrect($data['phone']);
            //Data are ok to be written in db
            $createdUserEntity = $this->persistenceInterface->create($data);

            //save user creation in tracker
            $detailsTracker = [
                'id'=> $createdUserEntity['id']
            ];

            $this->trackerService->writeAction($createdUserEntity['id'],
                Constants::TRACKER_ROLE_USER,
                null,
                null,
                Constants::TRACKER_CREATE_USER,
                $detailsTracker);

            //Send welcome email
            //SK A FAIRE

            $userResponse->status = 201;
            $userResponse->statusText = 'Created';

        } catch (GaelOException $e) {
            $userResponse->status = 400;
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
        if($this->persistenceInterface->isExistingEmail($data['email'])) throw new GaelOException('Already Existing Username');
        if($this->persistenceInterface->isExistingUsername($data['username'])) throw new GaelOException('Already used Email');
    }
}

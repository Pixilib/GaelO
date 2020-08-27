<?php

namespace App\GaelO\UseCases\Login;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class Login{

    public function __construct(PersistenceInterface $userRepository){
        $this->userRepository = $userRepository;
    }

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse){

        $user = $this->persistenceInterface->getUserByUsername($loginRequest->username);

        $inputHashedPassword = LaravelFunctionAdapter::hash($loginRequest->password);
        $dateNow = new \DateTime();
        $dateUpdatePassword= new \DateTime($user['last_password_update']);
        $attempts = $user['attempts'];
        $delayDay=$dateUpdatePassword->diff($dateNow)->format("%a");

        if($user['password'] !== $inputHashedPassword){
            $loginResponse->status = 401;
            $loginResponse->statusText = "Unauthorized";
            $this->increaseAttemptCount($user);

        } else {

            if($user['status'] === Constants::USER_STATUS_UNCONFIRMED){
                if($user['password_temporary'] === $inputHashedPassword){
                    $loginResponse->status = 429;
                    $loginResponse->statusText = "Unconfirmed";
                }else{
                    $loginResponse->status = 401;
                    $loginResponse->statusText = "Unauthorized";
                    $this->increaseAttemptCount($user);
                }

            }else if ($user['status'] === Constants::USER_STATUS_BLOCKED){
                $this->sendBlockedEmail($user);
                $loginResponse->status = 427;
                $loginResponse->statusText = "Blocked";
            }else if ($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay>90){
                $loginResponse->status = 428;
                $loginResponse->statusText = "Password Expired";
            }else if($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay<90 && $attempts<3){
                $this->updateDbOnSuccess($user);
                $loginResponse->status = 200;
                $loginResponse->statusText = "OK";
            }else{
                throw new \Exception('Unknown Login Failure');
            }
        }

    }

    private function increaseAttemptCount($user){
        $user['attempts'] = $user['attempts']++;

        if( $user['attempts'] > 2 ){
            $user['status'] = Constants::USER_STATUS_BLOCKED;
            if ($user['attempts'] == 3) $this->writeBlockedAccountInTracker($user);
            $this->sendBlockedEmail($user);
        }
        $this->persistenceInterface->update($user['id'], $user);
    }

    //SK TODO
    private function writeBlockedAccountInTracker($user){

    }

    //SK TODO
    private function sendBlockedEmail($user){

    }

    //SK TODO
    private function sendAdminConnectedEmail(){

    }

    private function updateDbOnSuccess($user){
        $user['last_connexion'] = Util::now();
        $user['attempts'] = 0;
        $this->persistenceInterface->update($user['id'], $user);
        if ($user['administrator']) $this->sendAdminConnectedEmail();
    }


}

<?php

namespace App\GaelO\UseCases\Login;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;

class Login{

    public function __construct(PersistenceInterface $userRepository, MailServices $mailService, TrackerService $trackerService){
        $this->userRepository = $userRepository;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
    }

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse){

        $user = $this->userRepository->getUserByUsername($loginRequest->username);

        $passwordCheck = LaravelFunctionAdapter::checkHash($loginRequest->password, $user['password']);
        $dateNow = new \DateTime();
        $dateUpdatePassword= new \DateTime($user['last_password_update']);
        $attempts = $user['attempts'];
        $delayDay=$dateUpdatePassword->diff($dateNow)->format("%a");

        if($user['status'] === Constants::USER_STATUS_UNCONFIRMED){
            $tempPasswordCheck = LaravelFunctionAdapter::checkHash($loginRequest->password, $user['password_temporary']);
            if($tempPasswordCheck){
                $loginResponse->status = 432;
                $loginResponse->statusText = "Unconfirmed";
            }else{
                $loginResponse->status = 433;
                $loginResponse->statusText = "Wrong Temporary Password";
                $this->increaseAttemptCount($user);
            }
            return;
        }

        if( !$passwordCheck ){
            $loginResponse->status = 401;
            $loginResponse->statusText = "Wrong Password";
            $this->increaseAttemptCount($user);

        } else {

            if ($user['status'] === Constants::USER_STATUS_BLOCKED){
                $this->sendBlockedEmail($user);
                $loginResponse->status = 434;
                $loginResponse->statusText = "Blocked";
            }else if ($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay>90){
                $loginResponse->status = 435;
                $loginResponse->statusText = "Password Expired";
            }else if($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay<90 && $attempts<3){
                $this->updateDbOnSuccess($user, $loginRequest->ip);
                $loginResponse->status = 200;
                $loginResponse->statusText = "OK";
            }else{
                throw new \Exception('Unknown Login Failure');
            }
        }

    }

    private function increaseAttemptCount($user){
        $user['attempts'] = ++$user['attempts'];

        if( $user['attempts'] >= 3 ){
            $user['status'] = Constants::USER_STATUS_BLOCKED;
            if ($user['attempts'] == 3) $this->writeBlockedAccountInTracker($user);
            $this->sendBlockedEmail($user);
        }
        $this->userRepository->update($user['id'], $user);
    }

    private function writeBlockedAccountInTracker($user){
        $this->trackerService->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_ACCOUNT_BLOCKED, ['message'=> 'Account Blocked']);
    }

    private function sendBlockedEmail($user){
        $this->mailService->sendAccountBlockedMessage($user['username'], $user['email']);
    }

    private function updateDbOnSuccess($user, $ip){
        $user['last_connexion'] = Util::now();
        $user['attempts'] = 0;
        $this->userRepository->update($user['id'], $user);
        if ($user['administrator']) {
            $this->mailService->sendAdminConnectedMessage($user['username'], $ip);
        }
    }


}

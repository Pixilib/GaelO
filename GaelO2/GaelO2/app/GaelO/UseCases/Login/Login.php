<?php

namespace App\GaelO\UseCases\Login;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;
use Exception;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Login{

    public function __construct(PersistenceInterface $userRepository, MailServices $mailService, TrackerService $trackerService){
        $this->userRepository = $userRepository;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
    }

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse){

        try{

            $user = $this->userRepository->getUserByUsername($loginRequest->username);

            $passwordCheck = null;

            if($user['status'] !== Constants::USER_STATUS_UNCONFIRMED && $user['password'] !== null) $passwordCheck = LaravelFunctionAdapter::checkHash($loginRequest->password, $user['password']);
            $dateNow = new \DateTime();
            $dateUpdatePassword= new \DateTime($user['last_password_update']);
            $attempts = $user['attempts'];
            $delayDay=$dateUpdatePassword->diff($dateNow)->format("%a");

            if($user['status'] === Constants::USER_STATUS_UNCONFIRMED ){
                $tempPasswordCheck = LaravelFunctionAdapter::checkHash($loginRequest->password, $user['password_temporary']);
                if($tempPasswordCheck){
                    $loginResponse->body = ['id' => $user['id'], 'errorMessage' => 'Unconfirmed'];
                    $loginResponse->status = 400;
                    $loginResponse->statusText = "Bad Request";
                } else {
                    $this->increaseAttemptCount($user);
                    throw new GaelOBadRequestException('Wrong Temporary Password, remaining'.(3- ++$user['attempts'] ).' attempts');
                }
                return;
            }

            if( $passwordCheck !== null && !$passwordCheck && $user['status'] !== Constants::USER_STATUS_BLOCKED){
                $this->increaseAttemptCount($user);
                $loginResponse->body = ['errorMessage' => 'Wrong Password remaining'.(3- ++$user['attempts'] ).' attempts'];
                $loginResponse->status = 401;
                $loginResponse->statusText = "Unauthorized";


            } else {

                if ($user['status'] === Constants::USER_STATUS_BLOCKED){
                    $this->sendBlockedEmail($user);
                    throw new GaelOBadRequestException('Account Blocked');

                }else if ($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay>90){
                    $loginResponse->body = ['id' => $user['id'], 'errorMessage' => 'Password Expired'];
                    $loginResponse->status = 400;
                    $loginResponse->statusText = "Bad Request";
                }else if($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay<90 && $attempts<3){
                    $this->updateDbOnSuccess($user, $loginRequest->ip);
                    $loginResponse->status = 200;
                    $loginResponse->statusText = "OK";
                }else{
                    throw new Exception("Unkown Login Failure");
                }
            }



        } catch (GaelOException $e){

            $loginResponse->body = $e->getErrorBody();
            $loginResponse->status = $e->statusCode;
            $loginResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
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
        $user['last_connection'] = Util::now();
        $user['attempts'] = 0;
        $this->userRepository->update($user['id'], $user);
        if ($user['administrator']) {
            $this->mailService->sendAdminConnectedMessage($user['username'], $ip);
        }
    }


}

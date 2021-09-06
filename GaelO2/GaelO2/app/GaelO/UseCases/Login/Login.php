<?php

namespace App\GaelO\UseCases\Login;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\HashInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\MailServices;
use Exception;

class Login
{

    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private HashInterface $hashInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, MailServices $mailService, TrackerRepositoryInterface $trackerRepositoryInterface, HashInterface $hashInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->hashInterface = $hashInterface;
    }

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse)
    {

        try {

            $user = $this->userRepositoryInterface->getUserByUsername($loginRequest->username);

            $passwordCheck = null;

            if ($user['status'] !== Constants::USER_STATUS_UNCONFIRMED && $user['password'] !== null) $passwordCheck = $this->hashInterface->checkHash($loginRequest->password, $user['password']);
            $dateNow = new \DateTime();
            $dateUpdatePassword = new \DateTime($user['last_password_update']);
            $attempts = $user['attempts'];
            $delayDay = $dateUpdatePassword->diff($dateNow)->format("%a");

            if ($user['status'] === Constants::USER_STATUS_UNCONFIRMED) {
                $tempPasswordCheck = $this->hashInterface->checkHash($loginRequest->password, $user['password_temporary']);
                if ($tempPasswordCheck) {
                    $loginResponse->body = ['id' => $user['id'], 'errorMessage' => 'Unconfirmed'];
                    $loginResponse->status = 400;
                    $loginResponse->statusText = "Bad Request";
                } else {
                    $remainingAttempts = $this->increaseAttemptCount($user);
                    if ($remainingAttempts) {
                        $loginResponse->body = ['errorMessage' => 'Wrong Password remaining ' . $remainingAttempts . ' attempts'];
                    } else {
                        $loginResponse->body = ['errorMessage' => 'Account Blocked'];
                    }
                    $loginResponse->status = 401;
                    $loginResponse->statusText = "Unauthorized";
                }
                return;
            }

            if ($passwordCheck !== null && !$passwordCheck && $user['status'] !== Constants::USER_STATUS_BLOCKED) {
                $remainingAttempts = $this->increaseAttemptCount($user);
                if ($remainingAttempts) {
                    $loginResponse->body = ['errorMessage' => 'Wrong Password remaining ' . $remainingAttempts . ' attempts'];
                } else {
                    $loginResponse->body = ['errorMessage' => 'Account Blocked'];
                }
                $loginResponse->status = 401;
                $loginResponse->statusText = "Unauthorized";

                return;
            }

            if ($user['status'] === Constants::USER_STATUS_BLOCKED) {
                $this->sendBlockedEmail($user);
                throw new GaelOBadRequestException('Account Blocked');
            } else if ($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay > 90) {
                $loginResponse->body = ['id' => $user['id'], 'errorMessage' => 'Password Expired'];
                $loginResponse->status = 400;
                $loginResponse->statusText = "Bad Request";
            } else if ($user['status'] === Constants::USER_STATUS_ACTIVATED && $delayDay < 90 && $attempts < 3) {
                $this->updateDbOnSuccess($user, $loginRequest->ip);
                $loginResponse->status = 200;
                $loginResponse->statusText = "OK";
            } else {
                throw new Exception("Unkown Login Failure");
            }

        } catch (GaelOException $e) {

            $loginResponse->body = $e->getErrorBody();
            $loginResponse->status = $e->statusCode;
            $loginResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return if still remaining attempts
     */
    private function increaseAttemptCount($user): bool
    {
        $attempts = ++$user['attempts'];

        //Update DB
        $this->userRepositoryInterface->updateUserAttempts($user['id'], $attempts);
        //Block account if needed
        if ($user['attempts'] >= 3) {
            if ($user['attempts'] == 3) $this->writeBlockedAccountInTracker($user);
            $this->userRepositoryInterface->updateUserStatus($user['id'], Constants::USER_STATUS_BLOCKED);
            $this->sendBlockedEmail($user);
        }

        return ($attempts < 3);
    }

    private function writeBlockedAccountInTracker(array $user)
    {
        $this->trackerRepositoryInterface->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_ACCOUNT_BLOCKED, ['message' => 'Account Blocked']);
    }

    private function sendBlockedEmail(array $user)
    {
        $this->mailService->sendAccountBlockedMessage($user['username'], $user['email'], $user['id']);
    }

    private function updateDbOnSuccess($user, $ip)
    {
        $this->userRepositoryInterface->resetAttemptsAndUpdateLastConnexion($user['id']);
        if ($user['administrator']) {
            $this->mailService->sendAdminConnectedMessage($user['username'], $ip);
        }
    }
}
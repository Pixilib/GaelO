<?php

namespace App\GaelO\UseCases\Login;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOUnauthorizedException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Util;
use Exception;

class Login
{
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, MailServices $mailService, TrackerRepositoryInterface $trackerRepositoryInterface, FrameworkInterface $frameworkInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(LoginRequest $loginRequest, LoginResponse $loginResponse)
    {

        try {
            $user = $this->userRepositoryInterface->getUserByEmail($loginRequest->email);

            $passwordCheck = false;
            if ($user['password'] !== null) $passwordCheck = $this->frameworkInterface->checkHash($loginRequest->password, $user['password']);

            $attempts = $user['attempts'];

            //If wrong password increase attempt count and refuse connexion
            if (!$passwordCheck) {
                $newAttemptCount = $this->increaseAttemptCount($user);
                $remainingAttempts = $this->getRemainingAttempts($newAttemptCount);
                if ($remainingAttempts === 0) {
                    $this->sendBlockedEmail($user);
                }
                throw new GaelOUnauthorizedException('Unknown email/password pair');
            }

            //if everything OK => Login
            if ($user['email_verified_at'] !== null && $attempts < 3) {
                $this->updateDbOnSuccess($user, $loginRequest->ip);
                $loginResponse->onboarded = !(Util::isVersionHigher(FrameworkAdapter::getConfig('onboarding_version'), $user['onboarding_version']));
                $loginResponse->status = 200;
                $loginResponse->statusText = "OK";
                //should not happen
            } else {
                throw new GaelOUnauthorizedException("Unknown email/password");
            }
        } catch (AbstractGaelOException $e) {
            $loginResponse->body = $e->getErrorBody();
            $loginResponse->status = $e->statusCode;
            $loginResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            $loginResponse->body = ['errorMessage' => "Unknown email/password"];
            $loginResponse->status = 401;
            $loginResponse->statusText = 'Unauthorized';
        }
    }

    /**
     * Return if still remaining attempts
     */
    private function increaseAttemptCount($user): int
    {
        $attempts = ++$user['attempts'];

        //Update DB
        $this->userRepositoryInterface->updateUserAttempts($user['id'], $attempts);
        //Block account if needed
        if ($user['attempts'] >= 3) {
            if ($user['attempts'] == 3) $this->writeBlockedAccountInTracker($user);
            $this->sendBlockedEmail($user);
        }

        return $attempts;
    }

    private function getRemainingAttempts($attemptCount): int
    {
        if ($attemptCount < 3) return (3 - $attemptCount);
        else return 0;
    }

    private function writeBlockedAccountInTracker(array $user)
    {
        $this->trackerRepositoryInterface->writeAction($user['id'], Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_ACCOUNT_BLOCKED, ['message' => 'Account Blocked']);
    }

    private function sendBlockedEmail(array $user)
    {
        $this->mailService->sendAccountBlockedMessage($user['email'], $user['id']);
    }

    private function updateDbOnSuccess($user, $ip)
    {
        $this->userRepositoryInterface->resetAttemptsAndUpdateLastConnexion($user['id']);
        if ($user['administrator']) {
            $this->mailService->sendAdminConnectedMessage($user['email'], $ip);
        }
    }
}

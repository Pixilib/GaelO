<?php

namespace App\GaelO\UseCases\ModifyUserOnboarding;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Util;
use Exception;

class ModifyUserOnboarding
{
    private UserRepositoryInterface $userRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyUserOnboardingRequest $modifyUserOnboardingRequest, ModifyUserOnboardingResponse $modifyUserOnboardingResponse)
    {

        try {

            $currentUserId = $modifyUserOnboardingRequest->currentUserId;
            $userId = $modifyUserOnboardingRequest->userId;
            $newOnboardingVersion = $modifyUserOnboardingRequest->onboardingVersion;

            $this->checkAuthorization($currentUserId, $userId);

            $user = $this->userRepositoryInterface->find($userId);

            if (!Util::isVersionHigher($newOnboardingVersion, $user['onboarding_version'])) {
                throw new GaelOBadRequestException('Cannot downgrade onboarding version');
            }

            $this->userRepositoryInterface->updateUser(
                $user['id'],
                $user['lastname'],
                $user['firstname'],
                $user['email'],
                $user['phone'],
                $user['administrator'],
                $user['center_code'],
                $user['job'],
                $user['orthanc_address'],
                $user['orthanc_login'],
                $user['orthanc_password'],
                $newOnboardingVersion,
                false
            );

            $details = [
                'modified_user_id' => $userId,
                'new_onboarding_version' => $newOnboardingVersion
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserOnboardingResponse->status = 200;
            $modifyUserOnboardingResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $modifyUserOnboardingResponse->body = $e->getErrorBody();
            $modifyUserOnboardingResponse->status = $e->statusCode;
            $modifyUserOnboardingResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $userId)
    {
        if ($currentUserId !== $userId) throw new GaelOForbiddenException();
    }
}

<?php

namespace App\GaelO\UseCases\ModifyUserIdentification;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationRequest;
use App\GaelO\UseCases\ModifyUserIdentification\ModifyUserIdentificationResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\UseCases\CreateUser\CreateUser;
use Exception;

class ModifyUserIdentification
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(
        UserRepositoryInterface $userRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        FrameworkInterface $frameworkInterface
    ) {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ModifyUserIdentificationRequest $modifyUserIdentificationRequest, ModifyUserIdentificationResponse $modifyUserIdentificationResponse): void
    {

        try {

            $currentUserId = $modifyUserIdentificationRequest->currentUserId;
            $userId = $modifyUserIdentificationRequest->userId;

            $this->checkAuthorization($currentUserId, $userId);

            $user = $this->userRepositoryInterface->find($userId);

            $resetEmailValidation = false;
            if ($modifyUserIdentificationRequest->email !== $user['email']) {
                CreateUser::checkEmailValid($modifyUserIdentificationRequest->email);
                $knownEmail = $this->userRepositoryInterface->isExistingEmail($modifyUserIdentificationRequest->email);
                if ($knownEmail) throw new GaelOConflictException("Email Already Known");
                $resetEmailValidation = true;
            }

            $this->userRepositoryInterface->updateUser(
                $user['id'],
                $modifyUserIdentificationRequest->lastname,
                $modifyUserIdentificationRequest->firstname,
                $modifyUserIdentificationRequest->email,
                $modifyUserIdentificationRequest->phone,
                $user['administrator'],
                $user['center_code'],
                $user['job'],
                $user['orthanc_address'],
                $user['orthanc_login'],
                $user['orthanc_password'],
                $resetEmailValidation
            );

            if ($resetEmailValidation) {
                $this->frameworkInterface->sendRegisteredEventForEmailVerification($user['id']);
            }

            $details = [
                'modified_user_id' => $userId,
                'lastname' => $modifyUserIdentificationRequest->lastname,
                'firstname' => $modifyUserIdentificationRequest->firstname,
                'email' => $modifyUserIdentificationRequest->email,
                'phone' => $modifyUserIdentificationRequest->phone
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_USER, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserIdentificationResponse->status = 200;
            $modifyUserIdentificationResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $modifyUserIdentificationResponse->body = $e->getErrorBody();
            $modifyUserIdentificationResponse->status = $e->statusCode;
            $modifyUserIdentificationResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $userId)
    {
        if ($currentUserId !== $userId) throw new GaelOForbiddenException();
    }
}

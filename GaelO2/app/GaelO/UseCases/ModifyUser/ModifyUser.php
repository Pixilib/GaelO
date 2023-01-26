<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\UseCases\CreateUser\CreateUser;
use Exception;

class ModifyUser
{

    private AuthorizationUserService $authorizationUserService;
    private UserRepositoryInterface $userRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(
        AuthorizationUserService $authorizationUserService,
        UserRepositoryInterface $userRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        FrameworkInterface $frameworkInterface
    ) {
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse): void
    {

        try {

            $this->checkAuthorization($modifyUserRequest->currentUserId);

            $user = $this->userRepositoryInterface->find($modifyUserRequest->userId);

            CreateUser::checkFormComplete($modifyUserRequest);
            CreateUser::checkEmailValid($modifyUserRequest->email);
            CreateUser::checkPhoneCorrect($modifyUserRequest->phone);

            $resetEmailValidation = false;

            if ($user['email'] !== $modifyUserRequest->email) {
                $resetEmailValidation = true;
                $knownEmail = $this->userRepositoryInterface->isExistingEmail($modifyUserRequest->email);
                if ($knownEmail) throw new GaelOConflictException("Email Already Known");
            }


            $this->userRepositoryInterface->updateUser(
                $user['id'],
                $modifyUserRequest->lastname,
                $modifyUserRequest->firstname,
                $modifyUserRequest->email,
                $modifyUserRequest->phone,
                $modifyUserRequest->administrator,
                $modifyUserRequest->centerCode,
                $modifyUserRequest->job,
                $modifyUserRequest->orthancAddress,
                $modifyUserRequest->orthancLogin,
                $modifyUserRequest->orthancPassword != null ? $this->frameworkInterface->encrypt($modifyUserRequest->orthancPassword) : $user['orthanc_password'],
                $user['onboarding_version'],
                $resetEmailValidation
            );


            if ($resetEmailValidation) {
                $this->frameworkInterface->sendRegisteredEventForEmailVerification($user['id']);
            }

            $details = [
                'modified_user_id' => $modifyUserRequest->userId
            ];

            $this->trackerRepositoryInterface->writeAction($modifyUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserResponse->status = 200;
            $modifyUserResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $modifyUserResponse->body = $e->getErrorBody();
            $modifyUserResponse->status = $e->statusCode;
            $modifyUserResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization($userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}

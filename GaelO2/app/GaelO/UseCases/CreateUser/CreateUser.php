<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\PhoneNumberInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\MailServices;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;

class CreateUser
{

    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private FrameworkInterface $frameworkInterface;
    private MailServices $mailService;

    public function __construct(AuthorizationUserService $authorizationUserService, UserRepositoryInterface $userRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface, FrameworkInterface $frameworkInterface, MailServices $mailService)
    {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->mailService = $mailService;
    }

    public function execute(CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse): void
    {

        try {

            $currentUserId = $createUserRequest->currentUserId;
            //In case creation is asked by a supervisor
            $studyName = $createUserRequest->studyName;

            $this->checkAuthorization($currentUserId, $studyName);
            self::checkFormComplete($createUserRequest);
            self::checkEmailValid($createUserRequest->email);
            self::checkPhoneCorrect($createUserRequest->phone);

            $knownEmail = $this->userRepositoryInterface->isExistingEmail($createUserRequest->email);
            if ($knownEmail) throw new GaelOConflictException("Email Already Known");


            //In no Exception thrown by checks methods, user are ok to be written in db
            $createdUserEntity = $this->userRepositoryInterface->createUser(
                $createUserRequest->lastname,
                $createUserRequest->firstname,
                $createUserRequest->email,
                $createUserRequest->phone,
                $createUserRequest->administrator,
                $createUserRequest->centerCode,
                $createUserRequest->job,
                $createUserRequest->orthancAddress,
                $createUserRequest->orthancLogin,
                $createUserRequest->orthancPassword != null ? $this->frameworkInterface->encrypt($createUserRequest->orthancPassword) : null,
            );

            //Send reset password link.
            $emailSendSuccess = $this->frameworkInterface->sendResetPasswordLink($createUserRequest->email);
            if (!$emailSendSuccess) throw new GaelOException('Error Sending Reset Email');

            $this->mailService->sendCreatedUserMessage($createUserRequest->email);


            //Save action in Tracker
            $detailsTracker = [
                'created_user_id' => $createdUserEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::TRACKER_ROLE_ADMINISTRATOR,
                null,
                null,
                Constants::TRACKER_CREATE_USER,
                $detailsTracker
            );

            $createUserResponse->body = ['id' => $createdUserEntity['id']];
            $createUserResponse->status = 201;
            $createUserResponse->statusText = 'Created';
        } catch (AbstractGaelOException $e) {
            $createUserResponse->body = $e->getErrorBody();
            $createUserResponse->status = $e->statusCode;
            $createUserResponse->statusText = $e->statusText;
        }
    }

    private function checkAuthorization(int $userId, ?string $studyName): void
    {

        $this->authorizationUserService->setUserId($userId);

        if ($studyName == null) {
            //If no study name specified user shall be admin
            if (!$this->authorizationUserService->isAdmin()) throw new GaelOForbiddenException();
        } else {
            //Else shall be supervisor in the study
            if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
                throw new GaelOForbiddenException();
            }
        }
    }

    public static function checkFormComplete(CreateUserRequest|ModifyUserRequest $userRequest): void
    {
        if (
            !isset($userRequest->job)
            || !isset($userRequest->email)
            || !is_numeric($userRequest->centerCode)
            || !isset($userRequest->administrator)
        ) {
            throw new GaelOBadRequestException('Form incomplete');
        }
    }

    public static function checkEmailValid(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new GaelOBadRequestException('Not a valid email format');
        }
    }

    public static function checkPhoneCorrect(?string $phone): void
    {
        //If contains non number caracters throw error
        if ($phone != null && !FrameworkAdapter::make(PhoneNumberInterface::class)::isValidPhoneNumber($phone)) {
            throw new GaelOBadRequestException('Not a valid phone number');
        }
    }
}

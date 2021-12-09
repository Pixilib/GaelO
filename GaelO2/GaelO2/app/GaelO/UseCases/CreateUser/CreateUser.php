<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\PhoneNumberInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\MailServices;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\Util;

class CreateUser
{

    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;
    private MailServices $mailService;

    public function __construct(AuthorizationUserService $authorizationUserService, UserRepositoryInterface $userRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailService)
    {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse): void
    {

        try {
            $this->checkAuthorization($createUserRequest->currentUserId);
            //Generate password
            $passwordTemporary = Util::generateNewTempPassword();

            self::checkFormComplete($createUserRequest);
            self::checkEmailValid($createUserRequest->email);
            self::checkPhoneCorrect($createUserRequest->phone);

            $knownEmail = $this->userRepositoryInterface->isExistingEmail($createUserRequest->email);
            if ($knownEmail) throw new GaelOConflictException("Email Already Known");


            //In no Exception thrown by checks methods, user are ok to be written in db
            $createdUserEntity = $this->userRepositoryInterface->createUser(
                $createUserRequest->lastname,
                $createUserRequest->firstname,
                Constants::USER_STATUS_UNCONFIRMED,
                $createUserRequest->email,
                $createUserRequest->phone,
                $createUserRequest->administrator,
                $createUserRequest->centerCode,
                $createUserRequest->job,
                $createUserRequest->orthancAddress,
                $createUserRequest->orthancLogin,
                $createUserRequest->orthancPassword
            );

            FrameworkAdapter::sendRegisteredEventForEmailVerification($createdUserEntity['id']);

            //Save action in Tracker
            $detailsTracker = [
                'createdUserId' => $createdUserEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction(
                $createUserRequest->currentUserId,
                Constants::TRACKER_ROLE_ADMINISTRATOR,
                null,
                null,
                Constants::TRACKER_CREATE_USER,
                $detailsTracker
            );

            //Send Welcom Email to give the plain password to new user.
            $this->mailService->sendCreatedAccountMessage(
                $createdUserEntity['email'],
                $createdUserEntity['firstname'] . ' ' . $createdUserEntity['lastname'],
                $passwordTemporary
            );

            $createUserResponse->body = ['id' => $createdUserEntity['id']];
            $createUserResponse->status = 201;
            $createUserResponse->statusText = 'Created';

        } catch (GaelOException $e) {

            $createUserResponse->body = $e->getErrorBody();
            $createUserResponse->status = $e->statusCode;
            $createUserResponse->statusText = $e->statusText;
        }
    }

    private function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        };
    }

    public static function checkFormComplete(CreateUserRequest|ModifyUserRequest $userRequest): void
    {
        if ( !isset($userRequest->job)
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
        if ($phone != null && !FrameworkAdapter::make(PhoneNumberInterface::class)::isValidPhoneNumber($phone) ) {
            throw new GaelOBadRequestException('Not a valid phone number');
        }
    }
}

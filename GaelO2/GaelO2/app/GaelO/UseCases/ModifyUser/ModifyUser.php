<?php

namespace App\GaelO\UseCases\ModifyUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\HashInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\UseCases\ModifyUser\ModifyUserRequest;
use App\GaelO\UseCases\ModifyUser\ModifyUserResponse;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\UseCases\CreateUser\CreateUser;
use App\GaelO\Util;
use Exception;

class ModifyUser
{

    private AuthorizationService $authorizationService;
    private UserRepositoryInterface $userRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailService;
    private HashInterface $hashInterface;

    public function __construct(AuthorizationService $authorizationService,
                            UserRepositoryInterface $userRepositoryInterface,
                            TrackerRepositoryInterface $trackerRepositoryInterface,
                            MailServices $mailService,
                            HashInterface $hashInterface)
    {
        $this->authorizationService = $authorizationService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->mailService = $mailService;
        $this->hashInterface = $hashInterface;
    }

    public function execute(ModifyUserRequest $modifyUserRequest, ModifyUserResponse $modifyUserResponse): void
    {

        try {

            $this->checkAuthorization($modifyUserRequest->currentUserId);

            $user = $this->userRepositoryInterface->find($modifyUserRequest->userId);

            if ($modifyUserRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
                //If unconfirmed generate a new temporary password
                $temporaryPassword = Util::generateNewTempPassword();
            } else {
                //Do not modify the current temporary password otherwise
                $temporaryPassword = $user['password_temporary'];
            }

            CreateUser::checkFormComplete($modifyUserRequest);
            CreateUser::checkEmailValid($modifyUserRequest->email);
            CreateUser::checkPhoneCorrect($modifyUserRequest->phone);

            if($user['username'] !== $modifyUserRequest->username){
                $knownUsername = $this->userRepositoryInterface->isExistingUsername($modifyUserRequest->username);
                if ($knownUsername) throw new GaelOConflictException("Username Already Used");
            }

            if($user['email'] !== $modifyUserRequest->email){
                $knownEmail = $this->userRepositoryInterface->isExistingEmail($modifyUserRequest->email);
                if ($knownEmail) throw new GaelOConflictException("Email Already Known");
            }


            $this->userRepositoryInterface->updateUser(
                $user['id'],
                $modifyUserRequest->username,
                $modifyUserRequest->lastname,
                $modifyUserRequest->firstname,
                $modifyUserRequest->status,
                $modifyUserRequest->email,
                $modifyUserRequest->phone,
                $modifyUserRequest->administrator,
                $modifyUserRequest->centerCode,
                $modifyUserRequest->job,
                $modifyUserRequest->orthancAddress,
                $modifyUserRequest->orthancLogin,
                $modifyUserRequest->orthancPassword,
                $temporaryPassword
            );


            if ($modifyUserRequest->status === Constants::USER_STATUS_UNCONFIRMED) {
                $this->mailService->sendResetPasswordMessage(
                    ($modifyUserRequest->firstname . ' ' . $modifyUserRequest->lastname),
                    $modifyUserRequest->username,
                    $temporaryPassword,
                    $modifyUserRequest->email
                );
            }

            $details = [
                'modified_user_id' => $modifyUserRequest->userId,
                'status' => $modifyUserRequest->status
            ];

            $this->trackerRepositoryInterface->writeAction($modifyUserRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $details);

            $modifyUserResponse->status = 200;
            $modifyUserResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $modifyUserResponse->body = $e->getErrorBody();
            $modifyUserResponse->status = $e->statusCode;
            $modifyUserResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        };
    }

    private function checkAuthorization($userId)
    {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if (!$this->authorizationService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}

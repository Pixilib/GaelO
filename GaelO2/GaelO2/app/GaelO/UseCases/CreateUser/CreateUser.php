<?php

namespace App\GaelO\UseCases\CreateUser;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\UseCases\CreateUser\CreateUserRequest;
use App\GaelO\UseCases\CreateUser\CreateUserResponse;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\UserService;

class CreateUser {

    private AuthorizationService $authorizationService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailService;
    private UserService $userService;

    public function __construct(AuthorizationService $authorizationService, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailService, UserService $userService){
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->authorizationService = $authorizationService;
        $this->userService = $userService;
     }

     public function execute(CreateUserRequest $createUserRequest, CreateUserResponse $createUserResponse) : void
    {

        try {
            $this->checkAuthorization($createUserRequest->currentUserId);
            //Generate password
            $password=substr(uniqid(), 1, 10);
            $passwordTemporary = LaravelFunctionAdapter::Hash($password);
            $createdUserEntity = $this->userService->createUser($createUserRequest, $passwordTemporary);

            //Save action in Tracker
            $detailsTracker = [
                'createdUserId'=> $createdUserEntity['id']
            ];

            $this->trackerRepositoryInterface->writeAction($createUserRequest->currentUserId,
                Constants::TRACKER_ROLE_ADMINISTRATOR,
                null,
                null,
                Constants::TRACKER_CREATE_USER,
                $detailsTracker);

            //Send Welcom Email to give the plain password to new user.
            $this->mailService->sendCreatedAccountMessage($createdUserEntity['email'],
                                $createdUserEntity['firstname'].' '.$createdUserEntity['lastname'],
                                $createdUserEntity['username'],
                                $password);

            $createUserResponse->status = 201;
            $createUserResponse->statusText = 'Created';

        } catch (GaelOException $e) {

            $createUserResponse->body = $e->getErrorBody();
            $createUserResponse->status = $e->statusCode;
            $createUserResponse->statusText = $e->statusText;

        }

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin($userId) ) {
            throw new GaelOForbiddenException();
        };
    }

}

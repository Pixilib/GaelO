<?php

namespace App\GaelO\UseCases\GetRolesInStudyFromUser;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use Exception;

class GetRolesInStudyFromUser{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationService $authorizationService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationService $authorizationService)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(GetRolesInStudyFromUserRequest $getRolesInStudyFromUserRequest, GetRolesInStudyFromUserResponse $getRolesInStudyFromUserResponse){

        try{
            $this->checkAuthorization($getRolesInStudyFromUserRequest->currentUserId, $getRolesInStudyFromUserRequest->userId);

            $roles = $this->userRepositoryInterface->getUsersRolesInStudy($getRolesInStudyFromUserRequest->currentUserId, $getRolesInStudyFromUserRequest->studyName);

            $getRolesInStudyFromUserResponse->body = $roles;
            $getRolesInStudyFromUserResponse->status = 200;
            $getRolesInStudyFromUserResponse->statusText = 'OK';

        } catch (GaelOException $e) {

            $getRolesInStudyFromUserResponse->body = $e->getErrorBody();
            $getRolesInStudyFromUserResponse->status = $e->statusCode;
            $getRolesInStudyFromUserResponse->statusText = $e->statusText;

        } catch (Exception $e) {
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $userId) : void
    {
        $this->authorizationService->setCurrentUserAndRole($currentUserId);
        if($currentUserId !== $userId && !$this->authorizationService->isAdmin()) throw new GaelOForbiddenException();
    }
}

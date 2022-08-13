<?php

namespace App\GaelO\UseCases\DeleteUserRole;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteUserRole
{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface  = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteUserRoleRequest $deleteUserRoleRequest, DeleteUserRoleResponse $deleteUserRoleResponse): void
    {

        try {

            $this->checkAuthorization($deleteUserRoleRequest->currentUserId);

            $study = $deleteUserRoleRequest->studyName;
            $role = $deleteUserRoleRequest->role;
            $userId = $deleteUserRoleRequest->userId;

            $this->userRepositoryInterface->deleteRoleForUser($userId, $study, $role);

            $actionDetails = [
                'deleted_role' => $role
            ];

            $this->trackerRepositoryInterface->writeAction($deleteUserRoleRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $study, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $deleteUserRoleResponse->status = 200;
            $deleteUserRoleResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $deleteUserRoleResponse->body = $e->getErrorBody();
            $deleteUserRoleResponse->status = $e->statusCode;
            $deleteUserRoleResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}

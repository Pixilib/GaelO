<?php

namespace App\GaelO\UseCases\GetValidatedDocumentationForRole;

use App\GaelO\Entities\RoleEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetValidatedDocumentationForRole
{
    private AuthorizationUserService $authorizationUserService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetValidatedDocumentationForRoleRequest $getValidatedDocumentationForRoleRequest, GetValidatedDocumentationForRoleResponse $getValidatedDocumentationForRoleResponse)
    {

        try {
            $studyName = $getValidatedDocumentationForRoleRequest->studyName;
            $role = $getValidatedDocumentationForRoleRequest->role;
            $currentUserId = $getValidatedDocumentationForRoleRequest->currentUserId;
            $userId = $getValidatedDocumentationForRoleRequest->userId;

            $this->checkAuthorization($currentUserId, $userId, $role, $studyName);

            $roleData = $this->userRepositoryInterface->getUserRoleInStudy($userId, $studyName, $role);

            $roleEntity = RoleEntity::fillFromDBReponseArray($roleData);

            $getValidatedDocumentationForRoleResponse->body = $roleEntity;
            $getValidatedDocumentationForRoleResponse->status = 200;
            $getValidatedDocumentationForRoleResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getValidatedDocumentationForRoleResponse->body = $e->getErrorBody();
            $getValidatedDocumentationForRoleResponse->status = $e->statusCode;
            $getValidatedDocumentationForRoleResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $userId, string $role, string $studyName)
    {
        if ($currentUserId !== $userId) throw new GaelOForbiddenException();
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed($role, $studyName)) {
            throw new GaelOForbiddenException('Role not allowed for this study');
        }
    }
}

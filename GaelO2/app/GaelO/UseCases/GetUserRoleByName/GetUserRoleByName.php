<?php

namespace App\GaelO\UseCases\GetUserRoleByName;

use App\GaelO\Entities\RoleEntity;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetUserRoleByName
{
    private AuthorizationUserService $authorizationUserService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetUserRoleByNameRequest $getUserRoleByNameRequest, GetUserRoleByNameResponse $getUserRoleByNameResponse)
    {

        try {
            $studyName = $getUserRoleByNameRequest->studyName;
            $role = $getUserRoleByNameRequest->role;
            $currentUserId = $getUserRoleByNameRequest->currentUserId;
            $userId = $getUserRoleByNameRequest->userId;

            $this->checkAuthorization($currentUserId, $userId, $role, $studyName);

            $roleData = $this->userRepositoryInterface->getUserRoleInStudy($userId, $studyName, $role);

            $roleEntity = RoleEntity::fillFromDBReponseArray($roleData);
            $studyEntity = StudyEntity::fillFromDBReponseArray($roleData['study']);
            $roleEntity->setStudyEntity($studyEntity);

            $getUserRoleByNameResponse->body = $roleEntity;
            $getUserRoleByNameResponse->status = 200;
            $getUserRoleByNameResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getUserRoleByNameResponse->body = $e->getErrorBody();
            $getUserRoleByNameResponse->status = $e->statusCode;
            $getUserRoleByNameResponse->statusText = $e->statusText;
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

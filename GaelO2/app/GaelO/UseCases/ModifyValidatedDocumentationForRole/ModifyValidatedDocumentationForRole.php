<?php

namespace App\GaelO\UseCases\ModifyValidatedDocumentationForRole;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class ModifyValidatedDocumentationForRole
{

    private AuthorizationUserService $authorizationUserService;
    private UserRepositoryInterface $userRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyValidatedDocumentationForRoleRequest $modifyValidatedDocumentationForRoleRequest, ModifyValidatedDocumentationForRoleResponse $modifyValidatedDocumentationForRoleResponse)
    {

        try {
            $studyName = $modifyValidatedDocumentationForRoleRequest->studyName;
            $role = $modifyValidatedDocumentationForRoleRequest->role;
            $currentUserId = $modifyValidatedDocumentationForRoleRequest->currentUserId;
            $userId = $modifyValidatedDocumentationForRoleRequest->userId;
            $version = $modifyValidatedDocumentationForRoleRequest->version;

            $this->checkAuthorization($currentUserId, $userId, $studyName, $role);

            $this->userRepositoryInterface->updateValidatedDocumentationVersion($userId, $studyName, $role, $version);

            $actionDetails = [
                'validated_documentation_version' => $version
            ];

            $this->trackerRepositoryInterface->writeAction($userId, $role, $studyName, null, Constants::TRACKER_VALIDATED_DOCUMENTATION, $actionDetails);

            $modifyValidatedDocumentationForRoleResponse->status = 200;
            $modifyValidatedDocumentationForRoleResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $modifyValidatedDocumentationForRoleResponse->body = $e->getErrorBody();
            $modifyValidatedDocumentationForRoleResponse->status = $e->statusCode;
            $modifyValidatedDocumentationForRoleResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $userId, string $studyName, string $role)
    {
        if ($currentUserId !== $userId) throw new GaelOForbiddenException();
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isRoleAllowed($role, $studyName)) {
            throw new GaelOForbiddenException('Role not allowed for this study');
        }
    }
}

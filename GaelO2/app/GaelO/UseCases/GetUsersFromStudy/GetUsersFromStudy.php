<?php

namespace App\GaelO\UseCases\GetUsersFromStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetUsersFromStudy
{

    private AuthorizationStudyService $authorizationStudyService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetUsersFromStudyRequest $getUsersFromStudyRequest, GetUsersFromStudyResponse $getUsersFromStudyResponse): void
    {
        try {

            $studyName = $getUsersFromStudyRequest->studyName;
            $role = $getUsersFromStudyRequest->role;
            $currentUserId = $getUsersFromStudyRequest->currentUserId;

            $this->checkAuthorization($currentUserId, $role, $studyName);

            $dbData = $this->userRepositoryInterface->getUsersFromStudy($studyName);

            $responseArray = [];
            foreach ($dbData as $data) {
                $userEntity = [];
                if ($role === Constants::ROLE_SUPERVISOR) {
                    $userEntity = UserEntity::fillMinimalFromDBReponseArray($data);
                } else if ($role === Constants::ROLE_ADMINISTRATOR) {
                    $userEntity = UserEntity::fillFromDBReponseArray($data);
                }

                $rolesArray = array_map(function ($roleData) use ($studyName) {
                    if ($roleData['study_name'] == $studyName) return $roleData['name'];
                    else return null;
                }, $data['roles']);
                //filter empty location
                $rolesArray = array_filter($rolesArray, function ($role) {
                    if ($role === null) return false;
                    else return true;
                });
                //Rearange array to start as 0 without associative keys
                $rolesArray = array_values($rolesArray);
                $userEntity->addRoles($rolesArray);
                $responseArray[] = $userEntity;
            }

            $getUsersFromStudyResponse->body = $responseArray;
            $getUsersFromStudyResponse->status = 200;
            $getUsersFromStudyResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getUsersFromStudyResponse->body = $e->getErrorBody();
            $getUsersFromStudyResponse->status = $e->statusCode;
            $getUsersFromStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $askedRole, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($userId);

        if ($askedRole === Constants::ROLE_SUPERVISOR) {
            if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
                throw new GaelOForbiddenException();
            };
        } else if ($askedRole === CONSTANTS::ROLE_ADMINISTRATOR) {
            if (!$this->authorizationStudyService->getAuthorizationUserService()->isAdmin()) {
                throw new GaelOForbiddenException();
            };
        }
    }
}

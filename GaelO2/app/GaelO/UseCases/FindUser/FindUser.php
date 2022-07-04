<?php

namespace App\GaelO\UseCases\FindUser;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\UseCases\FindUser\FindUserRequest;
use App\GaelO\UseCases\FindUser\FindUserResponse;
use Exception;

class FindUser
{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(FindUserRequest $findUserRequest, FindUserResponse $findUserResponse): void
    {

        try {
            $email = $findUserRequest->email;

            $this->checkAuthorization($findUserRequest->currentUserId, $findUserRequest->studyName);

            $dbData = $this->userRepositoryInterface->getUserByEmail($email);
            $userId = $dbData['id'];

            $findUserResponse->body = ['id' => $userId];
            $findUserResponse->status = 200;
            $findUserResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $findUserResponse->body = $e->getErrorBody();
            $findUserResponse->status = $e->statusCode;
            $findUserResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $studyName)
    {
        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($userId);

        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        };
    }
}

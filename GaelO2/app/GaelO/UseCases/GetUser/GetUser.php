<?php

namespace App\GaelO\UseCases\GetUser;

use App\GaelO\Entities\UserEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\UseCases\GetUser\GetUserRequest;
use App\GaelO\UseCases\GetUser\GetUserResponse;
use Exception;

class GetUser
{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetUserRequest $getUserRequest, GetUserResponse $getUserResponse): void
    {

        try {

            $id = $getUserRequest->id;

            $this->checkAuthorization($getUserRequest->currentUserId, $id);

            if ($id === null) {
                $dbData = $this->userRepositoryInterface->getAll($getUserRequest->withTrashed);
                $responseArray = [];
                foreach ($dbData as $data) {
                    $responseArray[] = UserEntity::fillFromDBReponseArray($data);
                }
                $getUserResponse->body = $responseArray;
            } else {
                $dbData = $this->userRepositoryInterface->find($id);
                $responseEntity = UserEntity::fillFromDBReponseArray($dbData);
                $getUserResponse->body = $responseEntity;
            }
            $getUserResponse->status = 200;
            $getUserResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getUserResponse->body = $e->getErrorBody();
            $getUserResponse->status = $e->statusCode;
            $getUserResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, ?int $calledUserId)
    {
        $this->authorizationUserService->setUserId($userId);
        if ($this->authorizationUserService->isAdmin()) {
            return;
        } else {
            if ($calledUserId !== $userId) {
                throw new GaelOForbiddenException();
            }
        }
    }
}

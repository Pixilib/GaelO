<?php

namespace App\GaelO\UseCases\GetUserCenters;

use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetUserCenters
{
    private AuthorizationUserService $authorizationUserService;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(AuthorizationUserService $authorizationUserService, UserRepositoryInterface $userRepositoryInterface)
    {
        $this->authorizationUserService = $authorizationUserService;
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function execute(GetUserCentersRequest $getUserCentersRequest, GetUserCentersResponse $getUserCentersResponse)
    {

        try {

            $id = $getUserCentersRequest->userId;
            $this->checkAuthorization($getUserCentersRequest->currentUserId, $id);

            $centers = [];
            $mainCenter = $this->userRepositoryInterface->getUserMainCenter($id);
            $centers[] = CenterEntity::fillFromDBReponseArray($mainCenter);

            $affiliatedCenters = $this->userRepositoryInterface->getAffiliatedCenter($id);
            foreach($affiliatedCenters as $affiliatedCenter){
                $centers[] = CenterEntity::fillFromDBReponseArray($affiliatedCenter);
            }

            $getUserCentersResponse->status = 200;
            $getUserCentersResponse->statusText = 'OK';
            $getUserCentersResponse->body = $centers;

        } catch (AbstractGaelOException $e) {
            $getUserCentersResponse->body = $e->getErrorBody();
            $getUserCentersResponse->status = $e->statusCode;
            $getUserCentersResponse->statusText = $e->statusText;
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

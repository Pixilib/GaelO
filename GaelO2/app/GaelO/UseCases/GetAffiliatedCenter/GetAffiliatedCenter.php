<?php

namespace App\GaelO\UseCases\GetAffiliatedCenter;

use App\GaelO\Entities\CenterEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetAffiliatedCenter
{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetAffiliatedCenterRequest $getAffiliatedCenterRequest, GetAffiliatedCenterResponse $getAffiliatedCenterResponse)
    {

        try {
            $this->checkAuthorization($getAffiliatedCenterRequest->currentUserId);
            $affiliatedCenters = $this->userRepositoryInterface->getAffiliatedCenter($getAffiliatedCenterRequest->userId);
            $centerResponseArray = [];

            foreach ($affiliatedCenters as $center) {
                $centerResponseArray[]  = CenterEntity::fillFromDBReponseArray($center);
            }

            $getAffiliatedCenterResponse->body = $centerResponseArray;
            $getAffiliatedCenterResponse->status = 200;
            $getAffiliatedCenterResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getAffiliatedCenterResponse->body =  $e->getErrorBody();
            $getAffiliatedCenterResponse->status = $e->statusCode;
            $getAffiliatedCenterResponse->statusText = $e->statusCode;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        };
    }
}

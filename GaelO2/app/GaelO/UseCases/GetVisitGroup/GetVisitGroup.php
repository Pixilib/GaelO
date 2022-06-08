<?php

namespace App\GaelO\UseCases\GetVisitGroup;

use App\GaelO\Entities\VisitGroupEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Repositories\VisitGroupRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetVisitGroup
{

    private VisitGroupRepository $visitGroupRepository;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(VisitGroupRepository $visitGroupRepository, AuthorizationUserService $authorizationUserService)
    {
        $this->visitGroupRepository = $visitGroupRepository;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitTypeResponse)
    {

        try {

            $this->checkAuthorization($getVisitGroupRequest->currentUserId);
            $visitGroupData = $this->visitGroupRepository->find($getVisitGroupRequest->visitGroupId);
            $visitGroupEntity = VisitGroupEntity::fillFromDBReponseArray($visitGroupData);
            $getVisitTypeResponse->body = $visitGroupEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (GaelOException $e) {
            $getVisitTypeResponse->body = $e->getErrorBody();
            $getVisitTypeResponse->status = $e->statusCode;
            $getVisitTypeResponse->statusText = $e->statusText;
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

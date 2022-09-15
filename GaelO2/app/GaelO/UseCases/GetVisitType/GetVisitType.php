<?php

namespace App\GaelO\UseCases\GetVisitType;

use App\GaelO\Entities\VisitTypeEntity;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\VisitTypeRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetVisitType
{

    private VisitTypeRepositoryInterface $visitTypeRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(VisitTypeRepositoryInterface $visitTypeRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->visitTypeRepositoryInterface = $visitTypeRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse)
    {

        try {

            $this->checkAuthorization($getVisitTypeRequest->currentUserId);
            $visitType = $this->visitTypeRepositoryInterface->find($getVisitTypeRequest->visitTypeId, false);
            $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
            $getVisitTypeResponse->body = $visitTypeEntity;
            $getVisitTypeResponse->status = 200;
            $getVisitTypeResponse->statusText = 'OK';

        } catch (AbstractGaelOException $e) {
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

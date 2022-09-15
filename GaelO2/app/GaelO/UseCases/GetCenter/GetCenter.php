<?php

namespace App\GaelO\UseCases\GetCenter;

use App\GaelO\Entities\CenterEntity;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetCenter
{
    private CenterRepositoryInterface $centerRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(CenterRepositoryInterface $centerRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetCenterRequest $getCenterRequest, GetCenterResponse $getCenterResponse): void
    {
        try {
            $studyName = null;
            if (isset($getCenterRequest->studyName)) $studyName = $getCenterRequest->studyName;

            $this->checkAuthorization($getCenterRequest->currentUserId, $studyName);

            $code = $getCenterRequest->code;

            if ($code === null) {
                $centers = $this->centerRepositoryInterface->getAll();
                $response = [];
                foreach ($centers as $center) {
                    $response[] = CenterEntity::fillFromDBReponseArray($center);
                }
                $getCenterResponse->body = $response;
            } else {
                $center  = $this->centerRepositoryInterface->getCenterByCode($code);
                $getCenterResponse->body = CenterEntity::fillFromDBReponseArray($center);
            }

            $getCenterResponse->status = 200;
            $getCenterResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getCenterResponse->status = $e->statusCode;
            $getCenterResponse->statusText = $e->statusText;
            $getCenterResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, ?string $studyName)
    {
        $this->authorizationUserService->setUserId($userId);

        if ($studyName == null) {
            //If no study name specified user shall be admin
            if (!$this->authorizationUserService->isAdmin()) throw new GaelOForbiddenException();
        } else {
            //Else shall be supervisor in the study
            if (!$this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)) {
                throw new GaelOForbiddenException();
            }
        }
    }
}

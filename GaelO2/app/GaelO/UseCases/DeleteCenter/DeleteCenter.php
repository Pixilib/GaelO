<?php

namespace App\GaelO\UseCases\DeleteCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\CenterRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteCenter
{
    private CenterRepositoryInterface $centerRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(
        CenterRepositoryInterface $centerRepositoryInterface,
        AuthorizationUserService $authorizationUserService,
        TrackerRepositoryInterface $trackerRepositoryInterface
    ) {
        $this->centerRepositoryInterface = $centerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }


    public function execute(DeleteCenterRequest $deleteCenterRequest, DeleteCenterResponse $deleteCenterResponse)
    {

        try {

            $currentUserId = $deleteCenterRequest->currentUserId;
            $centerCode = $deleteCenterRequest->centerCode;

            $this->checkAuthorization($currentUserId);

            $users = $this->centerRepositoryInterface->getUsersOfCenter($centerCode);
            $patients = $this->centerRepositoryInterface->getPatientsOfCenter($centerCode);

            if (sizeof($users) > 0 || sizeof($patients) > 0) {
                throw new GaelOForbiddenException("Cannot Delete Center with attached users or patients");
            }

            $this->centerRepositoryInterface->deleteCenter($centerCode);

            $actionDetails = [
                'center_code' => $centerCode,
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_DELETE_CENTER, $actionDetails);

            $deleteCenterResponse->status = 200;
            $deleteCenterResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteCenterResponse->status = $e->statusCode;
            $deleteCenterResponse->statusText = $e->statusText;
            $deleteCenterResponse->body = $e->getErrorBody();
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

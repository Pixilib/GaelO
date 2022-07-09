<?php

namespace App\GaelO\UseCases\DeleteAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteAffiliatedCenter
{

    private UserRepositoryInterface $userRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteAffiliatedCenterRequest $deleteAffiliatedCenterRequest, DeleteAffiliatedCenterResponse $deleteAffiliatedCenterResponse)
    {

        try {

            $currentUserId = $deleteAffiliatedCenterRequest->currentUserId;

            $this->checkAuthorization($currentUserId);

            $this->userRepositoryInterface->deleteAffiliatedCenter($deleteAffiliatedCenterRequest->userId, $deleteAffiliatedCenterRequest->centerCode);

            $actionDetails = [
                'user_id' => $deleteAffiliatedCenterRequest->userId,
                'deleted_affiliated_center' => $deleteAffiliatedCenterRequest->centerCode
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

            $deleteAffiliatedCenterResponse->status = 200;
            $deleteAffiliatedCenterResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $deleteAffiliatedCenterResponse->status = $e->statusCode;
            $deleteAffiliatedCenterResponse->statusText = $e->statusText;
            $deleteAffiliatedCenterResponse->body = $e->getErrorBody();
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

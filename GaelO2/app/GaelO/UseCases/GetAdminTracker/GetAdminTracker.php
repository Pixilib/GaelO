<?php

namespace App\GaelO\UseCases\GetAdminTracker;

use App\GaelO\Constants\Constants;
use App\GaelO\Entities\TrackerEntity;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class GetAdminTracker
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(GetAdminTrackerRequest $getTrackerRequest, GetAdminTrackerResponse $getTrackerResponse): void
    {

        try {

            $this->checkAuthorization($getTrackerRequest->currentUserId);
            $admin = $getTrackerRequest->admin;

            if (filter_var($admin, FILTER_VALIDATE_BOOLEAN)) $dbData = $this->trackerRepositoryInterface->getTrackerOfRole(Constants::TRACKER_ROLE_ADMINISTRATOR);
            else $dbData = $this->trackerRepositoryInterface->getTrackerOfRole(Constants::TRACKER_ROLE_USER);

            $responseArray = [];
            foreach ($dbData as $data) {
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserDetails(UserEntity::fillMinimalFromDBReponseArray($data['user']));
                $responseArray[] = $trackerEntity;
            }

            $getTrackerResponse->body = $responseArray;
            $getTrackerResponse->status = 200;
            $getTrackerResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getTrackerResponse->body = $e->getErrorBody();
            $getTrackerResponse->status = $e->statusCode;
            $getTrackerResponse->statusText = $e->statusText;
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

<?php

namespace App\GaelO\UseCases\AddAffiliatedCenter;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class AddAffiliatedCenter
{

    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(AddAffiliatedCenterRequest $addAffiliatedCenterRequest, AddAffiliatedCenterResponse $addAffiliatedCenterResponse)
    {

        try {

            $currentUserId = $addAffiliatedCenterRequest->currentUserId;
            $userId = $addAffiliatedCenterRequest->userId;
            $centerCode = $addAffiliatedCenterRequest->centerCode;

            $this->checkAuthorization($currentUserId);

            $existingCenterCodeArray = $this->userRepositoryInterface->getAllUsersCenters($userId);

            //Check the request creation is not in Main or affiliated centers
            if (!in_array($centerCode, $existingCenterCodeArray)) {

                $this->userRepositoryInterface->addAffiliatedCenter($userId, $centerCode);

                $actionDetails = [
                    'user_id' => $userId,
                    'add_affiliated_center' => $centerCode
                ];
                $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_EDIT_USER, $actionDetails);

                $addAffiliatedCenterResponse->status = '201';
                $addAffiliatedCenterResponse->statusText = 'Created';
            } else {
                throw new GaelOConflictException('Center already affiliated to user');
            }
        } catch (AbstractGaelOException $e) {
            $addAffiliatedCenterResponse->status = $e->statusCode;
            $addAffiliatedCenterResponse->statusText = $e->statusText;
            $addAffiliatedCenterResponse->body = $e->getErrorBody();
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

<?php

namespace App\GaelO\UseCases\GetStudyTrackerByVisit;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Entities\TrackerEntity;
use App\GaelO\Constants\Constants;
use App\GaelO\Entities\UserEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use Exception;

class GetStudyTrackerByVisit
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationVisitService $authorizationVisitService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationVisitService $authorizationVisitService)
    {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationVisitService = $authorizationVisitService;
    }

    public function execute(GetStudyTrackerByVisitRequest $getStudyTrackerByVisitRequest, GetStudyTrackerByVisitResponse $getStudyTrackerByVisitResponse): void
    {
        try {

            $this->checkAuthorization($getStudyTrackerByVisitRequest->currentUserId, $getStudyTrackerByVisitRequest->visitId, $getStudyTrackerByVisitRequest->studyName);

            $dbData = $this->trackerRepositoryInterface->getTrackerOfVisitId($getStudyTrackerByVisitRequest->visitId, $getStudyTrackerByVisitRequest->studyName);

            $responseArray = [];
            foreach ($dbData as $data) {
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserDetails(UserEntity::fillMinimalFromDBReponseArray($data['user']));
                $responseArray[] = $trackerEntity;
            }

            $getStudyTrackerByVisitResponse->body = $responseArray;
            $getStudyTrackerByVisitResponse->status = 200;
            $getStudyTrackerByVisitResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $getStudyTrackerByVisitResponse->body = $e->getErrorBody();
            $getStudyTrackerByVisitResponse->status = $e->statusCode;
            $getStudyTrackerByVisitResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId, string $studyName)
    {
        $this->authorizationVisitService->setUserId($userId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);
        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}

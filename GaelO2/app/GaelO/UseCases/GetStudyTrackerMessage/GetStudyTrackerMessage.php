<?php

namespace App\GaelO\UseCases\GetStudyTrackerMessage;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Entities\TrackerEntity;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use Exception;

class GetStudyTrackerMessage
{

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AuthorizationStudyService $authorizationStudyService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, AuthorizationStudyService $authorizationStudyService)
    {
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(GetStudyTrackerMessageRequest $getStudyTrackerMessageRequest, GetStudyTrackerMessageResponse $getStudyTrackerMessageResponse): void
    {

        try {

            $this->checkAuthorization($getStudyTrackerMessageRequest->currentUserId, $getStudyTrackerMessageRequest->studyName);

            $dbData = $this->trackerRepositoryInterface->getTrackerOfMessages();
            $responseArray = [];
            foreach ($dbData as $data) {
                $trackerEntity = TrackerEntity::fillFromDBReponseArray($data);
                $trackerEntity->setUserData($data['user']);
                $responseArray[] = $trackerEntity;
            }

            $getStudyTrackerMessageResponse->body = $responseArray;
            $getStudyTrackerMessageResponse->status = 200;
            $getStudyTrackerMessageResponse->statusText = 'OK';
        } catch (GaelOException $e) {
            $getStudyTrackerMessageResponse->body = $e->getErrorBody();
            $getStudyTrackerMessageResponse->status = $e->statusCode;
            $getStudyTrackerMessageResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {
        $this->authorizationStudyService->setUserId($currentUserId);
        $this->authorizationStudyService->setStudyName($studyName);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }
}

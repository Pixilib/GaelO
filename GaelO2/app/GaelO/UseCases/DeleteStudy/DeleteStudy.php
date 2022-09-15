<?php

namespace App\GaelO\UseCases\DeleteStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class DeleteStudy
{

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(DeleteStudyRequest $deleteStudyRequest, DeleteStudyResponse $deleteStudyResponse)
    {

        try {

            if (empty($deleteStudyRequest->reason)) throw new GaelOBadRequestException('reason must be specified');

            $currentUserId = $deleteStudyRequest->currentUserId;
            $this->checkAuthorization($currentUserId);
            $studyName = $deleteStudyRequest->studyName;
            $this->studyRepositoryInterface->delete($studyName);

            $details = [
                'reason' => $deleteStudyRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $studyName, null, Constants::TRACKER_DEACTIVATE_STUDY, $details);

            $deleteStudyResponse->status = 200;
            $deleteStudyResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $deleteStudyResponse->body = $e->getErrorBody();
            $deleteStudyResponse->status = $e->statusCode;
            $deleteStudyResponse->statusText = $e->statusText;
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

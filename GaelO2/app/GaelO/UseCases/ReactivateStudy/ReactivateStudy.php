<?php

namespace App\GaelO\UseCases\ReactivateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

class ReactivateStudy
{
    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationUserService $authorizationUserService;
    private TrackerRepository $trackerRepository;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationUserService $authorizationUserService, TrackerRepository $trackerRepository)
    {
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->trackerRepository = $trackerRepository;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse)
    {

        try {

            if (empty($reactivateStudyRequest->reason)) throw new GaelOBadRequestException('Reason must be specified');

            $currentUserId = $reactivateStudyRequest->currentUserId;
            $studyName = $reactivateStudyRequest->studyName;
            $reason = $reactivateStudyRequest->reason;

            $this->checkAuthorization($currentUserId);

            $this->studyRepositoryInterface->reactivateStudy($studyName);

            $actionsDetails = [
                'reactivated_study' => $studyName,
                'reason' => $reason
            ];
            $this->trackerRepository->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_REACTIVATE_STUDY, $actionsDetails);

            $reactivateStudyResponse->status = 200;
            $reactivateStudyResponse->statusText = 'OK';
        } catch (AbstractGaelOException $e) {
            $reactivateStudyResponse->body = $e->getErrorBody();
            $reactivateStudyResponse->status = $e->statusCode;
            $reactivateStudyResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization($userId)
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin()) {
            throw new GaelOForbiddenException();
        }
    }
}

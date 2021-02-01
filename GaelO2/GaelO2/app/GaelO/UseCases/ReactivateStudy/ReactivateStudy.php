<?php

namespace App\GaelO\UseCases\ReactivateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\StudyRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Services\AuthorizationService;
use Exception;

class ReactivateStudy {

    private StudyRepositoryInterface $studyRepositoryInterface;
    private AuthorizationService $authorizationService;
    private TrackerRepository $trackerRepository;

    public function __construct(StudyRepositoryInterface $studyRepositoryInterface, AuthorizationService $authorizationService, TrackerRepository $trackerRepository){
        $this->studyRepositoryInterface = $studyRepositoryInterface;
        $this->trackerRepository = $trackerRepository;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){

        try {

            $this->checkAuthorization($reactivateStudyRequest->currentUserId);

            $this->studyRepositoryInterface->reactivateStudy($reactivateStudyRequest->studyName);

            $actionsDetails = [
                'reactivatedStudy' => $reactivateStudyRequest->studyName
            ];
            $this->trackerRepository->writeAction($reactivateStudyRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_REACTIVATE_STUDY, $actionsDetails);

            $reactivateStudyResponse->status = 200;
            $reactivateStudyResponse->statusText = 'OK';

        } catch (GaelOException $e){

            $reactivateStudyResponse->status = $e->statusCode;
            $reactivateStudyResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization($userId)  {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin() ) {
            throw new GaelOForbiddenException();
        };
    }

}

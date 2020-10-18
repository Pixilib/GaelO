<?php

namespace App\GaelO\UseCases\ReactivateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class ReactivateStudy {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(ReactivateStudyRequest $reactivateStudyRequest, ReactivateStudyResponse $reactivateStudyResponse){

        $this->persistenceInterface->reactivateStudy($reactivateStudyRequest->studyName);

        $actionsDetails = [
            'reactivatedStudy' => $reactivateStudyRequest->studyName
        ];
        $this->trackerService->writeAction($reactivateStudyRequest->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_REACTIVATE_STUDY, $actionsDetails);

        $reactivateStudyResponse->status = 200;
        $reactivateStudyResponse->statusText = 'OK';
    }

}

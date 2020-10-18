<?php

namespace App\GaelO\UseCases\DeleteStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class DeleteStudy {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService) {
        $this->persistenceInterface=$persistenceInterface;
        $this->trackerService=$trackerService;
    }

    public function execute(DeleteStudyRequest $deleteStudyQuery, DeleteStudyResponse $deleteStudyResponse){

        $studyName = $deleteStudyQuery->studyName;
        $this->persistenceInterface->delete($studyName);

        $this->trackerService->writeAction($deleteStudyQuery->currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, $studyName, null, Constants::TRACKER_DEACTIVATE_STUDY, []);

        $deleteStudyResponse->status = 200;
        $deleteStudyResponse->statusText = 'OK';
    }

}

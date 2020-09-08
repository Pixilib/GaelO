<?php

namespace App\GaelO\UseCases\DeleteStudy;

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

        $deleteStudyResponse->status = 200;
        $deleteStudyResponse->statusText = 'OK';
    }

}

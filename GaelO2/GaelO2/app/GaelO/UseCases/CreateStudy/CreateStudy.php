<?php

namespace App\GaelO\UseCases\CreateStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class CreateStudy {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;
    }

    public function execute(CreateStudyRequest $createStudyRequest, CreateStudyResponse $createStudyResponse){
        $studyName = $createStudyRequest->studyName;
        $patientCodePreffix = $createStudyRequest->patientCodePreffix;

        $this->persistenceInterface->addStudy($studyName, $patientCodePreffix);

        $currentUserId=$createStudyRequest->currentUserId;
        $actionDetails = [
            'studyName'=>$studyName,
            'patientCodePreffix'=> $patientCodePreffix
        ];

        $this->trackerService->writeAction($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, null, null, Constants::TRACKER_CREATE_STUDY, $actionDetails);

        $createStudyResponse->status = 200;
        $createStudyResponse->statusText = 'OK';

    }

}

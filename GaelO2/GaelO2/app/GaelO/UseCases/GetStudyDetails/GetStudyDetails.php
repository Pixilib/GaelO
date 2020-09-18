<?php

namespace App\GaelO\UseCases\GetStudyDetails;

use App\GaelO\Interfaces\PersistenceInterface;

class GetStudyDetails {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetStudyDetailsRequest $getStudyDetailsRequest, GetStudyDetailsResponse $getStudyDetailsResponse) : void {

        $visitGroups = $this->persistenceInterface->getStudiesVisitGroup($getStudyDetailsRequest->studyName);

        $getStudyDetailsResponse->body = $visitGroups;
        $getStudyDetailsResponse->status = 200;
        $getStudyDetailsResponse->statusText = 'OK';

    }
}

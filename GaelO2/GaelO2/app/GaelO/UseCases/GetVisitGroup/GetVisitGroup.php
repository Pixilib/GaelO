<?php

namespace App\GaelO\UseCases\GetVisitGroup;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitGroupRequest $getVisitGroupRequest, GetVisitGroupResponse $getVisitGroupResponse) : void {

        $visitGroups = $this->persistenceInterface->getStudiesVisitGroup($getVisitGroupRequest->studyName);

        $getVisitGroupResponse->body = $visitGroups;
        $getVisitGroupResponse->status = 200;
        $getVisitGroupResponse->statusText = 'OK';

    }
}

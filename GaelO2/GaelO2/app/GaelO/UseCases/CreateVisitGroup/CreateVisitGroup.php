<?php

namespace App\GaelO\UseCases\CreateVisitGroup;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;

class CreateVisitGroup {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService){

        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(CreateVisitGroupRequest $createVisitGroupRequest, CreateVisitGroupResponse $createVisitGroupResponse) : void {

        $existingVisitGroup = $this->persistenceInterface->isExistingVisitGroup($createVisitGroupRequest->studyName,
                                                        $createVisitGroupRequest->modality);

        if($existingVisitGroup) {
            $createVisitGroupResponse->body = ['errorMessage' => 'Conflict'];
            $createVisitGroupResponse->status = 209;
            $createVisitGroupResponse->statusText = "Conflict";
            return;
        }

        $this->persistenceInterface->createVisitGroup($createVisitGroupRequest->studyName, $createVisitGroupRequest->modality);

        $createVisitGroupResponse->status = 201;
        $createVisitGroupResponse->statusText = 'Created';

    }


}

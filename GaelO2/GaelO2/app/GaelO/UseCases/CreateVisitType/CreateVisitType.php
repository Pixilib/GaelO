<?php

namespace App\GaelO\UseCases\CreateVisitType;

use App\GaelO\Interfaces\PersistenceInterface;

class CreateVisitType {

    public function __construct(PersistenceInterface $persistenceInterface) {
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute( CreateVisitTypeRequest $createVisitTypeRequest, CreateVisitTypeResponse $createVisitTypeResponse ){

        $this->persistenceInterface->createVisitType(
                    $createVisitTypeRequest->visitGroupId,
                    $createVisitTypeRequest->name,
                    $createVisitTypeRequest->visitOrder,
                    $createVisitTypeRequest->localFormNeeded,
                    $createVisitTypeRequest->qcNeeded,
                    $createVisitTypeRequest->reviewNeeded,
                    $createVisitTypeRequest->optional,
                    $createVisitTypeRequest->limitLowDays,
                    $createVisitTypeRequest->limitUpDays,
                    $createVisitTypeRequest->anonProfile
        );

        $createVisitTypeResponse->status = 201;
        $createVisitTypeResponse->statusText = 'Created';
    }
}

<?php

namespace App\GaelO\UseCases\CreateVisit;

use App\GaelO\Exceptions\GaelOConflictException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;

class CreateVisit {

    public function __construct(PersistenceInterface $persistenceInterface, TrackerService $trackerService, VisitService $visitService){
        $this->visitService = $visitService;
        $this->persistenceInterface = $persistenceInterface;
        $this->trackerService = $trackerService;

    }

    public function execute(CreateVisitRequest $createVisitRequest, CreateVisitResponse $createVisitResponse) : void {

        try{
            $existingVisit = $this->persistenceInterface->isExistingVisit($createVisitRequest->patientCode,
                                                            $createVisitRequest->visitTypeId);


            if($existingVisit) {
                throw new GaelOConflictException('Visit Already Created');
            }else{

                $this->visitService->createVisit(
                    $createVisitRequest->studyName,
                    $createVisitRequest->creatorUserId,
                    $createVisitRequest->patientCode,
                    $createVisitRequest->acquisitionDate,
                    $createVisitRequest->visitTypeId,
                    $createVisitRequest->statusDone,
                    $createVisitRequest->reasonForNotDone);

                $createVisitResponse->status = 201;
                $createVisitResponse->statusText = 'Created';
            }
        } catch (GaelOException $e){
            $createVisitResponse->status = $e->statusCode;
            $createVisitResponse->statusText = $e->statusText;
            $createVisitResponse->body = $e->getErrorBody();
        }


    }


}

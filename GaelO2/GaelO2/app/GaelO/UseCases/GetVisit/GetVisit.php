<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisit {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitTypeResponse){

        $visitData = $this->persistenceInterface->find($getVisitRequest->visitId);
        $visitEntity = VisitEntity::fillFromDBReponseArray($visitData);
        $getVisitTypeResponse->body = $visitEntity;
        $getVisitTypeResponse->status = 200;
        $getVisitTypeResponse->statusText = 'OK';

    }
}

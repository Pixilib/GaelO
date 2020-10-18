<?php

namespace App\GaelO\UseCases\GetVisitType;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisitType {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitTypeRequest $getVisitTypeRequest, GetVisitTypeResponse $getVisitTypeResponse){
        $visitType = $this->persistenceInterface->find($getVisitTypeRequest->visitTypeId);
        $visitTypeEntity = VisitTypeEntity::fillFromDBReponseArray($visitType);
        $getVisitTypeResponse->body = $visitTypeEntity;
        $getVisitTypeResponse->status = 200;
        $getVisitTypeResponse->statusText = 'OK';
    }
}

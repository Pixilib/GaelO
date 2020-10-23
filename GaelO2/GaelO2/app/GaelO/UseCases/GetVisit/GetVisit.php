<?php

namespace App\GaelO\UseCases\GetVisit;

use App\GaelO\Interfaces\PersistenceInterface;

class GetVisit {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetVisitRequest $getVisitRequest, GetVisitResponse $getVisitTypeResponse){
        $id = $getVisitRequest->visitId;
        if ($id == 0) {
            $dbData = $this->persistenceInterface->getAll();
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = VisitEntity::fillFromDBReponseArray($data);
            }
            $getVisitRequest->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->find($id);
            $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
            $getVisitRequest->body = $responseEntity;
        }
        $getVisitTypeResponse->body = $responseEntity;
        $getVisitTypeResponse->status = 200;
        $getVisitTypeResponse->statusText = 'OK';
    }
}

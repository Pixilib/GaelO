<?php

namespace App\GaelO\UseCases\GetPatientVisit;

use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetVisit\VisitEntity;

class GetPatientVisit {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetPatientVisitRequest $getPatientVisitRequest, GetPatientVisitResponse $getPatientVisitResponse){
        $visitId = $getPatientVisitRequest->visitId;
        $patientCode = $getPatientVisitRequest->patientCode;
        if ($visitId == 0) {
            $dbData = $this->persistenceInterface->getAll();
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = VisitEntity::fillFromDBReponseArray($data);
            }
            /*dd(array_filter(['a', 'b'], function ($element) {
                return $element = 'a';
            }));*/
            $getPatientVisitResponse->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->find($visitId);
            $responseEntity = VisitEntity::fillFromDBReponseArray($dbData);
            $getPatientVisitResponse->body = $responseEntity;
        }
        $getPatientVisitResponse->status = 200;
        $getPatientVisitResponse->statusText = 'OK';
    }
}

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
            //SK ICI IL FAUT DANS LE REPOSITORY AVOIR UNE METHODE QUI RECUPERE TOUTE LES VISITE DU PATIENT
            $dbData = $this->persistenceInterface->getAll();
            $dbData = array_filter($dbData, function ($element) use ($patientCode) {
                return $element['patient_code'] == $patientCode;
            });
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = VisitEntity::fillFromDBReponseArray($data);
            }
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

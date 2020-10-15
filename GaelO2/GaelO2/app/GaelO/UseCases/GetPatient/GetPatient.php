<?php

namespace App\GaelO\UseCases\GetPatient;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetPatient\GetPatientRequest;
use App\GaelO\UseCases\GetPatient\GetPatientResponse;

class GetPatient {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetPatientRequest $patientRequest, GetPatientResponse $patientResponse) : void
    {
        $id = $patientRequest->id;

        if ($id == 0) {
            $dbData = $this->persistenceInterface->getAll();
            $responseArray = [];
            foreach($dbData as $data){
                $responseArray[] = PatientEntity::fillFromDBReponseArray($data);
            }
            $patientResponse->body = $responseArray;
        } else {
            $dbData = $this->persistenceInterface->find($id);
            $responseEntity = PatientEntity::fillFromDBReponseArray($dbData);
            $patientResponse->body = $responseEntity;
        }
        $patientResponse->status = 200;
        $patientResponse->statusText = 'OK';

    }

}

?>

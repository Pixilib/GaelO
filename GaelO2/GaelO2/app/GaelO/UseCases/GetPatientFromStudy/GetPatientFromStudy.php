<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyRequest;
use App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudyResponse;
use App\GaelO\UseCases\GetPatient\PatientEntity;

class GetPatientFromStudy {

    public function __construct(PersistenceInterface $persistenceInterface){
        $this->persistenceInterface = $persistenceInterface;
    }

    public function execute(GetPatientFromStudyRequest $patientRequest, GetPatientFromStudyResponse $patientResponse) : void
    {
        $studyName = $patientRequest->studyName;

        $dbData = $this->persistenceInterface->getPatientsInStudy($studyName);
        $responseArray = [];
        foreach($dbData as $data){
            $data = $this->persistenceInterface->find($data);
            $responseArray[] = PatientEntity::fillFromDBReponseArray($data);
        }
        $patientResponse->body = $responseArray;
        $patientResponse->status = 200;
        $patientResponse->statusText = 'OK';

    }

}

?>

<?php

namespace App\GaelO\UseCases\CreatePatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\PersistenceInterface;

use App\GaelO\UseCases\CreatePatient\CreatePatientRequest;
use App\GaelO\UseCases\CreatePatient\CreatePatientResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\UseCases\CreatePatient\ImportPatient;
use App\GaelO\UseCases\GetPatient\PatientEntity;

class CreatePatient {

    public function __construct(TrackerService $trackerService, MailServices $mailService, ImportPatient $importPatient){
        $this->importPatient = $importPatient;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(CreatePatientRequest $createPatientRequest, CreatePatientResponse $createPatientResponse) : void
    {
        $arrayPatients = [];
        foreach($createPatientRequest->patients as $patient) {
            $arrayPatients[] = PatientEntity::fillFromRequest($patient);
        }
        $createPatientRequest->patients = $arrayPatients;
        $this->importPatient->setPatientEntities($createPatientRequest->patients);
        $this->importPatient->setStudyName($createPatientRequest->studyName);

        //Check form completion
        try {

            $this->importPatient->import();

            //Save action in Tracker
            $actionDetails['Success']=$this->importPatient->successList;
            $actionDetails['Fail']=$this->importPatient->failList;

            $createPatientResponse->body = [ 'success' => $this->importPatient->successList, 'fail' => $this->importPatient->failList];
            $createPatientResponse->status = 200;
            $createPatientResponse->statusText = 'OK';
            $this->trackerService->writeAction($createPatientRequest->currentUserCode, Constants::TRACKER_IMPORT_PATIENT, null, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);
            // + send email
        } catch (GaelOException $e) {
            //If Exception thrown by our buisness logic, handle it
            $createPatientResponse->status = 400;
            $createPatientResponse->statusText = $e->getMessage();
        }catch (\Exception $e) {
            //If execption thrown by framework, let the framework handle it
            throw $e;
        }
    }

}

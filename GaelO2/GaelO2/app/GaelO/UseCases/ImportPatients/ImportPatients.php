<?php

namespace App\GaelO\UseCases\ImportPatients;

use App\GaelO\Constants\Constants;

use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\UseCases\GetPatient\PatientEntity;

class ImportPatients {

    public function __construct(TrackerService $trackerService, MailServices $mailService, ImportPatientService $importPatient){
        $this->importPatient = $importPatient;
        $this->trackerService = $trackerService;
        $this->mailService = $mailService;
     }

     public function execute(ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse) : void
    {
        $arrayPatients = [];
        foreach($importPatientsRequest->patients as $patient) {
            $arrayPatients[] = PatientEntity::fillFromRequest($patient);
        }
        $importPatientsRequest->patients = $arrayPatients;
        $this->importPatient->setPatientEntities($importPatientsRequest->patients);
        $this->importPatient->setStudyName($importPatientsRequest->studyName);

        //Check form completion
        try {

            $this->importPatient->import();

            //Save action in Tracker
            $actionDetails['Success']=$this->importPatient->successList;
            $actionDetails['Fail']=$this->importPatient->failList;

            $importPatientsResponse->body = [ 'success' => $this->importPatient->successList, 'fail' => $this->importPatient->failList];
            $importPatientsResponse->status = 200;
            $importPatientsResponse->statusText = 'OK';

            $this->trackerService->writeAction($importPatientsRequest->currentUserCode, Constants::TRACKER_IMPORT_PATIENT, null, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);
            $this->mailService->sendImportPatientMessage($importPatientsRequest->studyName, $this->importPatient->successList, $this->importPatient->failList);

        } catch (GaelOException $e) {
            //If Exception thrown by our buisness logic, handle it
            $importPatientsResponse->status = 400;
            $importPatientsResponse->statusText = $e->getMessage();
        }catch (\Exception $e) {
            //If execption thrown by framework, let the framework handle it
            throw $e;
        }
    }

}

<?php

namespace App\GaelO\UseCases\ImportPatients;

use App\GaelO\Constants\Constants;

use App\GaelO\UseCases\ImportPatients\ImportPatientsRequest;
use App\GaelO\UseCases\ImportPatients\ImportPatientsResponse;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\UseCases\GetPatient\PatientEntity;
use Exception;

class ImportPatients {

    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailService;
    private ImportPatientService $importPatient;
    private AuthorizationService $authorizationService;

    public function __construct(TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailService, ImportPatientService $importPatient, AuthorizationService $authorizationService){
        $this->importPatient = $importPatient;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailService = $mailService;
        $this->authorizationService = $authorizationService;
     }

     public function execute(ImportPatientsRequest $importPatientsRequest, ImportPatientsResponse $importPatientsResponse) : void
    {
        try{

            $this->checkAuthorization($importPatientsRequest->currentUserId, $importPatientsRequest->studyName);
            $arrayPatients = [];
            foreach($importPatientsRequest->patients as $patient) {
                $arrayPatients[] = PatientEntity::fillFromRequest($patient, $importPatientsRequest->studyName);
            }
            $importPatientsRequest->patients = $arrayPatients;
            $this->importPatient->setPatientEntities($importPatientsRequest->patients);
            $this->importPatient->setStudyName($importPatientsRequest->studyName);

            //Check form completion
            $this->importPatient->import();

            //Save action in Tracker
            $actionDetails['Success']=$this->importPatient->successList;
            $actionDetails['Fail']=$this->importPatient->failList;

            $importPatientsResponse->body = [ 'success' => $this->importPatient->successList, 'fail' => $this->importPatient->failList];
            $importPatientsResponse->status = 200;
            $importPatientsResponse->statusText = 'OK';

            $this->trackerRepositoryInterface->writeAction($importPatientsRequest->currentUserId, Constants::TRACKER_IMPORT_PATIENT, $importPatientsRequest->studyName, null, Constants::TRACKER_IMPORT_PATIENT, $actionDetails);
            $this->mailService->sendImportPatientMessage($importPatientsRequest->studyName, $this->importPatient->successList, $this->importPatient->failList);

        } catch (GaelOException $e){

            $importPatientsResponse->body = $e->getErrorBody();
            $importPatientsResponse->status = $e->statusCode;
            $importPatientsResponse->statusText = $e->statusText;

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $userId, string $studyName){
        $this->authorizationService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        if(! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        };

    }

}

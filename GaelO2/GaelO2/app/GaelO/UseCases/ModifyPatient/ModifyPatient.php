<?php

namespace App\GaelO\UseCases\ModifyPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\Util;
use Exception;

class ModifyPatient {

    private PatientRepositoryInterface $patientRepositoryInterface;
    private AuthorizationPatientService $authorizationPatientService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    public function __construct(PatientRepositoryInterface $patientRepositoryInterface,
                                AuthorizationPatientService $authorizationPatientService,
                                TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->patientRepositoryInterface = $patientRepositoryInterface;
        $this->authorizationPatientService = $authorizationPatientService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function execute(ModifyPatientRequest $modifyPatientRequest, ModifyPatientResponse $modifyPatientResponse){

        try{

            if (empty($modifyPatientRequest->reason)) throw new GaelOBadRequestException('Reason for patient edition must be sepecified');

            $this->checkAuthorization($modifyPatientRequest->currentUserId, $modifyPatientRequest->patientCode);

            $patientEntity = $this->patientRepositoryInterface->find($modifyPatientRequest->patientCode);

            $updatableData = ['firstname', 'lastname', 'gender', 'birthDay', 'birthMonth', 'birthYear',
            'registrationDate', 'investigatorName', 'centerCode'];

            //Format registration date
            if($modifyPatientRequest->registrationDate !== null) {
                $modifyPatientRequest->registrationDate = Util::formatUSDateStringToSQLDateFormat($modifyPatientRequest->registrationDate);
            }

            //Check Gender Validity
            if($modifyPatientRequest->gender !== null) {
                ImportPatientService::checkPatientGender($modifyPatientRequest->gender);
            }
            //Check BirthDate Validity
            ImportPatientService::checkCorrectBirthDate($modifyPatientRequest->birthDay, $modifyPatientRequest->birthMonth, $modifyPatientRequest->birthYear);

            $modifiedData = [];

            //Update each updatable data if specified in request
            foreach($updatableData as $data){
                if($modifyPatientRequest->$data !== null){
                    $patientEntity[Util::camelCaseToSnakeCase($data)] = $modifyPatientRequest->$data;
                    $modifiedData[Util::camelCaseToSnakeCase($data)] = $modifyPatientRequest->$data;
                }
            }

            $modifiedData['reason'] = $modifyPatientRequest->reason;

            $this->patientRepositoryInterface->update($modifyPatientRequest->patientCode, $patientEntity);
            $this->trackerRepositoryInterface->writeAction($modifyPatientRequest->currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_EDIT_PATIENT, $modifiedData);

            $modifyPatientResponse->status = 200;
            $modifyPatientResponse->statusText = 'OK';



        }catch(GaelOException $e){

            $modifyPatientResponse->body = $e->getErrorBody();
            $modifyPatientResponse->status = $e->statusCode;
            $modifyPatientResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $patientCode){
        $this->authorizationPatientService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationPatientService->setPatient($patientCode);
        if( ! $this->authorizationPatientService->isPatientAllowed()){
            throw new GaelOForbiddenException();
        };
    }
}

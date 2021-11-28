<?php

namespace App\GaelO\UseCases\ModifyPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationPatientService;
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

            $patientEntity = $this->patientRepositoryInterface->find($modifyPatientRequest->patientId);
            $studyName = $patientEntity['study_name'];
            $this->checkAuthorization($modifyPatientRequest->currentUserId, $modifyPatientRequest->patientId, $studyName);



            $updatableData = ['firstname', 'lastname', 'gender', 'birthDay', 'birthMonth', 'birthYear',
            'registrationDate', 'investigatorName', 'centerCode', 'inclusionStatus', 'withdrawReason', 'withdrawDate'];

            if($modifyPatientRequest->inclusionStatus === Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN){
                if(empty($modifyPatientRequest->withdrawDate) ||
                    empty($modifyPatientRequest->withdrawReason)
                ){
                    throw new GaelOBadRequestException('Withdraw Date and Reason must be specified for withdraw declaration');
                }
            } else {
                $patientEntity['withdraw_reason'] = null;
                $modifiedData['withdraw_reason'] = null;
                $patientEntity['withdraw_date'] = null;
                $modifiedData['withdraw_date'] = null;
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

            $this->patientRepositoryInterface->update($modifyPatientRequest->patientId, $patientEntity);
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

    private function checkAuthorization(int $userId, string $patientId, string $studyName){
        $this->authorizationPatientService->setUserId($userId);
        $this->authorizationPatientService->setPatientId($patientId);
        $this->authorizationPatientService->setStudyName($studyName);
        if( ! $this->authorizationPatientService->isPatientAllowed(Constants::ROLE_SUPERVISOR)){
            throw new GaelOForbiddenException();
        };
    }
}

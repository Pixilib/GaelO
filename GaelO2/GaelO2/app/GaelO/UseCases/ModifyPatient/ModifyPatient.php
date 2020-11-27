<?php

namespace App\GaelO\UseCases\ModifyPatient;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\ImportPatientService;
use App\GaelO\Services\TrackerService;
use App\GaelO\Util;
use Exception;

class ModifyPatient {

    public function __construct(PersistenceInterface $persistenceInterface,
                                AuthorizationPatientService $authorizationPatientService,
                                TrackerService $trackerService)
    {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationPatientService = $authorizationPatientService;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyPatientRequest $modifyPatientRequest, ModifyPatientResponse $modifyPatientResponse){

        try{

            $updatableData = ['firstname', 'lastname', 'gender', 'birthDay', 'birthMonth', 'birthYear',
            'registrationDate', 'investigatorName', 'centerCode'];

            $patientEntity = $this->persistenceInterface->find($modifyPatientRequest->patientCode);
            $modifiedData = [];

            //Update each updatable data if specified in request
            foreach($updatableData as $data){

                if($modifyPatientRequest->$data !== null){
                    $patientEntity[Util::camelCaseToSnakeCase($data)] = $modifyPatientRequest->$data;
                    $modifiedData[Util::camelCaseToSnakeCase($data)] = $modifyPatientRequest->$data;
                }
            }

            ImportPatientService::checkCorrectDate($modifyPatientRequest->birthDay, $modifyPatientRequest->birthMonth, $modifyPatientRequest->birthYear);

            $this->persistenceInterface->update($modifyPatientRequest->patientCode, $patientEntity);
            $this->trackerService->writeAction($modifyPatientRequest->currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_EDIT_PATIENT, $modifiedData);

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

    public function checkAuthorization(){

    }
}

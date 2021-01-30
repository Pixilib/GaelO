<?php

namespace App\GaelO\UseCases\ModifyPatientWithdraw;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\PatientRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Util;
use Exception;

class ModifyPatientWithdraw {

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

    public function execute(ModifyPatientWithdrawRequest $modifyPatientWithdrawRequest, ModifyPatientWithdrawResponse $modifyPatientWithdrawResponse){

        try{

            $this->checkAuthorization($modifyPatientWithdrawRequest->currentUserId, $modifyPatientWithdrawRequest->patientCode);

            $patientEntity = $this->patientRepositoryInterface->find($modifyPatientWithdrawRequest->patientCode);

            $modifiedData = [];

            //Handle Withdraw status update
            $patientEntity['inclusion_status'] = $modifyPatientWithdrawRequest->inclusionStatus;

            if($modifyPatientWithdrawRequest->inclusionStatus === Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN){

                if(empty($modifyPatientWithdrawRequest->withdrawDate) ||
                    empty($modifyPatientWithdrawRequest->withdrawReason)
                ){
                    throw new GaelOBadRequestException('Withdraw Date and Reason must be specified for withdraw declaration');
                }

                $patientEntity['withdraw_reason'] = $modifyPatientWithdrawRequest->withdrawReason;
                $patientEntity['withdraw_date'] = Util::formatUSDateStringToSQLDateFormat($modifyPatientWithdrawRequest->withdrawDate);

            }else{
                $patientEntity['withdraw_reason'] = null;
                $patientEntity['withdraw_date'] = null;
            }

            $modifiedData['inclusion_status'] = $modifyPatientWithdrawRequest->inclusionStatus;
            $modifiedData['withdraw_reason'] = $patientEntity['withdraw_reason'];
            $modifiedData['withdraw_date'] = $patientEntity['withdraw_date'];

            $this->patientRepositoryInterface->update($modifyPatientWithdrawRequest->patientCode, $patientEntity);
            $this->trackerRepositoryInterface->writeAction($modifyPatientWithdrawRequest->currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_PATIENT_WITHDRAW, $modifiedData);

            $modifyPatientWithdrawResponse->status = 200;
            $modifyPatientWithdrawResponse->statusText = 'OK';



        }catch(GaelOException $e){

            $modifyPatientWithdrawResponse->body = $e->getErrorBody();
            $modifyPatientWithdrawResponse->status = $e->statusCode;
            $modifyPatientWithdrawResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }

    public function checkAuthorization(int $userId, int $patientCode){
        $this->authorizationPatientService->setCurrentUserAndRole($userId, Constants::ROLE_SUPERVISOR);
        $this->authorizationPatientService->setPatient($patientCode);
        if( ! $this->authorizationPatientService->isPatientAllowed()){
            throw new GaelOForbiddenException();
        };
    }
}

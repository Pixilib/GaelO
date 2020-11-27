<?php

namespace App\GaelO\UseCases\ModifyPatientWithdraw;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Services\AuthorizationPatientService;
use App\GaelO\Services\TrackerService;
use Exception;

class ModifyPatientWithdraw {

    public function __construct(PersistenceInterface $persistenceInterface,
                                AuthorizationPatientService $authorizationPatientService,
                                TrackerService $trackerService)
    {
        $this->persistenceInterface = $persistenceInterface;
        $this->authorizationPatientService = $authorizationPatientService;
        $this->trackerService = $trackerService;
    }

    public function execute(ModifyPatientWithdrawRequest $modifyPatientWithdrawRequest, ModifyPatientWithdrawResponse $modifyPatientWithdrawResponse){

        try{

            $patientEntity = $this->persistenceInterface->find($modifyPatientWithdrawRequest->patientCode);
            $modifiedData = [];

            //Handle Withdraw status update
            $patientEntity['withdraw'] = $modifyPatientWithdrawRequest->withdraw;

            if($modifyPatientWithdrawRequest->withdraw){

                if($modifyPatientWithdrawRequest->withdrawDate === null || $modifyPatientWithdrawRequest->withdrawReason === null){
                    throw new GaelOBadRequestException('Withdraw Date and Reason must be specified for withdraw declaration');
                }

                $patientEntity['withdraw_reason'] = $modifyPatientWithdrawRequest->withdrawReason;
                $patientEntity['withdraw_date'] = $modifyPatientWithdrawRequest->withdrawDate;

            }else{
                $patientEntity['withdraw_reason'] = null;
                $patientEntity['withdraw_date'] = null;
            }

            $modifiedData['withdraw'] = $modifyPatientWithdrawRequest->withdraw;
            $modifiedData['withdraw_reason'] = $patientEntity['withdraw_reason'];
            $modifiedData['withdraw_date'] = $patientEntity['withdraw_date'];

            $this->persistenceInterface->update($modifyPatientWithdrawRequest->patientCode, $patientEntity);
            $this->trackerService->writeAction($modifyPatientWithdrawRequest->currentUserId, Constants::ROLE_SUPERVISOR, $patientEntity['study_name'], null, Constants::TRACKER_PATIENT_WITHDRAW, $modifiedData);

            $modifyPatientWithdrawRequest->status = 200;
            $modifyPatientWithdrawRequest->statusText = 'OK';



        }catch(GaelOException $e){

            $modifyPatientWithdrawRequest->body = $e->getErrorBody();
            $modifyPatientWithdrawRequest->status = $e->statusCode;
            $modifyPatientWithdrawRequest->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }

    }
}

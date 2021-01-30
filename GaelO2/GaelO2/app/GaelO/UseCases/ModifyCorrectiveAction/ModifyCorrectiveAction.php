<?php

namespace App\GaelO\UseCases\ModifyCorrectiveAction;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;
use Exception;

class ModifyCorrectiveAction{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitService $visitService;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct( AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailServices)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(ModifyCorrectiveActionRequest $modifyCorrectiveActionRequest, ModifyCorrectiveActionResponse $modifyCorrectiveActionResponse){

        try{
            $visitContext = $this->visitService->getVisitContext($modifyCorrectiveActionRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $patientCode = $visitContext['patient']['center_code'];
            $visitType = $visitContext['visit_type']['name'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $stateInvestigatorForm = $visitContext['state_investigator_form'];
            $currentQcStatus = $visitContext['state_quality_control'];

            if($currentQcStatus !== Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED){
                throw new GaelOForbiddenException();
            }

            if(  ! in_array($stateInvestigatorForm, array(Constants::INVESTIGATOR_FORM_DONE, Constants::INVESTIGATOR_FORM_NOT_NEEDED) ) ){
                throw new GaelOForbiddenException();
            }

            $this->checkAuthorization($modifyCorrectiveActionRequest->currentUserId, $modifyCorrectiveActionRequest->visitId);

            $this->visitService->setCorrectiveAction(
                    $modifyCorrectiveActionRequest->visitId,
                    $modifyCorrectiveActionRequest->currentUserId,
                    $modifyCorrectiveActionRequest->newSeriesUploaded,
                    $modifyCorrectiveActionRequest->newInvestigatorForm,
                    $modifyCorrectiveActionRequest->correctiveActionDone,
                    $modifyCorrectiveActionRequest->comment
            );

            $actionDetails = [
                'patient_code'=>$patientCode,
                'visit_type'=>$visitType,
                'vist_group_modality'=>$visitModality,
                'new_series'=>$modifyCorrectiveActionRequest->newSeriesUploaded,
                'new_investigator_form'=>$modifyCorrectiveActionRequest->newInvestigatorForm,
                'comment'=>$modifyCorrectiveActionRequest->comment,
                'corrective_action_applyed'=>$modifyCorrectiveActionRequest->correctiveActionDone,
            ];

            $this->trackerRepositoryInterface->writeAction(
                $modifyCorrectiveActionRequest->currentUserId,
                Constants::ROLE_INVESTIGATOR,
                $studyName,
                $modifyCorrectiveActionRequest->visitId,
                Constants::TRACKER_CORRECTIVE_ACTION,
                $actionDetails
            );

            //Send Email
            $this->mailServices->sendCorrectiveActionMessage(
                $modifyCorrectiveActionRequest->currentUserId,
                $studyName,
                $modifyCorrectiveActionRequest->correctiveActionDone,
                $patientCode,
                $visitModality,
                $visitType
            );

            $modifyCorrectiveActionResponse->status = 200;
            $modifyCorrectiveActionResponse->statusText = 'OK';

        }catch(GaelOException $e){

            $modifyCorrectiveActionResponse->body = $e->getErrorBody();
            $modifyCorrectiveActionResponse->status = $e->statusCode;
            $modifyCorrectiveActionResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, int $visitId) : void {
        //Check user has controller role in the visit
        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_INVESTIGATOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }



}

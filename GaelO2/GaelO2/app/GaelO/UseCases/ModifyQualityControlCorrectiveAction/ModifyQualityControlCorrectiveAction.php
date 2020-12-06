<?php

namespace App\GaelO\UseCases\ModifyQualityControlCorrectiveAction;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\TrackerService;
use App\GaelO\Services\VisitService;
use App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControlRequest;
use App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControlResponse;
use Exception;

class ModifyQualityControlCorrectiveAction{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitService $visitService;
    private TrackerService $trackerService;
    private MailServices $mailServices;

    public function __construct( AuthorizationVisitService $authorizationVisitService, VisitService $visitService, TrackerService $trackerService, MailServices $mailServices)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitService = $visitService;
        $this->trackerService = $trackerService;
        $this->mailServices = $mailServices;
    }

    public function execute(ModifyQualityControlCorrectiveActionRequest $modifyQualityControlCorrectiveActionRequest, ModifyQualityControlCorrectiveActionResponse $modifyQualityControlCorrectiveActionResponse){
        try{
            $visitContext = $this->visitService->getVisitContext($modifyQualityControlCorrectiveActionRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $patientCode = $visitContext['patient']['center_code'];
            $visitType = $visitContext['visit_type']['name'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $centerCode = $visitContext['patient']['center_code'];
            $creatorId = $visitContext['creator_user_id'];
            $localFormNeeded = $visitContext['visit_type']['local_form_needed'];
            $stateInvestigatorForm = $visitContext[''];

            if($modifyQualityControlCorrectiveActionRequest->stateQc !== Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED){
                throw new GaelOForbiddenException();
            }

            if(  ! in_array($stateInvestigatorForm, array(Constants::INVESTIGATOR_FORM_DONE, Constants::INVESTIGATOR_FORM_NOT_NEEDED) ) ){
                throw new GaelOForbiddenException();
            }

            $this->checkAuthorization($modifyQualityControlCorrectiveActionRequest->currentUserId, $modifyQualityControlCorrectiveActionRequest->visitId);


            $this->visitService->setCorrectiveAction(
                    $modifyQualityControlCorrectiveActionRequest->visitId,
                    $modifyQualityControlCorrectiveActionRequest->currentUserId,
                    $modifyQualityControlCorrectiveActionRequest->newSeriesUploaded,
                    $modifyQualityControlCorrectiveActionRequest->newInvestigatorForm,
                    $modifyQualityControlCorrectiveActionRequest->correctiveActionDone,
                    $modifyQualityControlCorrectiveActionRequest->comment
            );


            $actionDetails = [
                'patient_code'=>$patientCode,
                'visit_type'=>$visitType,
                'vist_group_modality'=>$visitModality,
                'new_series'=>$modifyQualityControlCorrectiveActionRequest->newSeriesUploaded,
                'new_investigator_form'=>$modifyQualityControlCorrectiveActionRequest->newInvestigatorForm,
                'comment'=>$modifyQualityControlCorrectiveActionRequest->comment,
                'corrective_action_applyed'=>$modifyQualityControlCorrectiveActionRequest->correctiveActionDone,
            ];

            $this->trackerService->writeAction(
                $modifyQualityControlCorrectiveActionRequest->currentUserId,
                Constants::ROLE_INVESTIGATOR,
                $studyName,
                $modifyQualityControlCorrectiveActionRequest->visitId,
                Constants::TRACKER_CORRECTIVE_ACTION,
                $actionDetails
            );

            //SK RESTE EMAIL A FAIRE
            $this->mailServices->sendQcDecisionMessage(
                $creatorId,
                $modifyQualityControlRequest->currentUserId,
                $studyName,
                $centerCode,
                $modifyQualityControlRequest->stateQc,
                $patientCode,
                $visitModality,
                $visitType,
                $modifyQualityControlRequest->formQc ? 'Accepted ' : 'Refused',
                $modifyQualityControlRequest->imageQc ? 'Accepted ' : 'Refused',
                $modifyQualityControlRequest->formQcComment ?? 'None',
                $modifyQualityControlRequest->imageQcComment ?? 'None'
            );

            $modifyQualityControlCorrectiveActionResponse->status = 200;
            $modifyQualityControlCorrectiveActionResponse->statusText = 'OK';

        }catch(GaelOException $e){

            $modifyQualityControlCorrectiveActionResponse->body = $e->getErrorBody();
            $modifyQualityControlCorrectiveActionResponse->status = $e->statusCode;
            $modifyQualityControlCorrectiveActionResponse->statusText = $e->statusText;

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

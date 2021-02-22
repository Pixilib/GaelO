<?php

namespace App\GaelO\UseCases\ModifyCorrectiveAction;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class ModifyCorrectiveAction{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct( AuthorizationVisitService $authorizationVisitService, VisitRepositoryInterface $visitRepositoryInterface, TrackerRepositoryInterface $trackerRepositoryInterface, MailServices $mailServices)
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(ModifyCorrectiveActionRequest $modifyCorrectiveActionRequest, ModifyCorrectiveActionResponse $modifyCorrectiveActionResponse){

        try{
            $visitContext = $this->visitRepositoryInterface->getVisitContext($modifyCorrectiveActionRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $patientCode = $visitContext['patient']['center_code'];
            $visitType = $visitContext['visit_type']['name'];
            $localFormNeeded = $visitContext['visit_type']['local_form_needed'];
            $visitModality = $visitContext['visit_type']['visit_group']['modality'];
            $stateInvestigatorForm = $visitContext['state_investigator_form'];
            $currentQcStatus = $visitContext['state_quality_control'];
            $uploadStatus = $visitContext['upload_status'];

            //If form Needed, form need to be sent before making corrective action
            if($localFormNeeded  && $stateInvestigatorForm !== Constants::INVESTIGATOR_FORM_DONE ){
                throw new GaelOForbiddenException();
            }

            //If not Uploaded images can't perform Corrective action
            if( $uploadStatus !== Constants::UPLOAD_STATUS_DONE ){
                throw new GaelOForbiddenException();
            }

            $this->checkAuthorization($modifyCorrectiveActionRequest->currentUserId, $modifyCorrectiveActionRequest->visitId, $currentQcStatus);

            $this->visitRepositoryInterface->setCorrectiveAction(
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

    private function checkAuthorization(int $userId, int $visitId, string $currentQcStatus) : void {

        if($currentQcStatus !== Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED){
            throw new GaelOForbiddenException();
        }

        //Check user has controller role in the visit
        $this->authorizationVisitService->setCurrentUserAndRole($userId, Constants::ROLE_INVESTIGATOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() ){
            throw new GaelOForbiddenException();
        }

    }



}

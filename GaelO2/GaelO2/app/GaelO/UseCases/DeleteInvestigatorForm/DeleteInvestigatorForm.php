<?php

namespace App\GaelO\UseCases\DeleteInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class DeleteInvestigatorForm{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(AuthorizationVisitService $authorizationVisitService,
            ReviewRepositoryInterface $reviewRepositoryInterface,
            VisitRepositoryInterface $visitRepositoryInterface,
            TrackerRepositoryInterface $trackerRepositoryInterface,
            MailServices $mailServices
            )
    {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->mailServices  = $mailServices;
    }

    public function execute(DeleteInvestigatorFormRequest $deleteInvestigatorFormRequest, DeleteInvestigatorFormResponse $DeleteInvestigatorFormResponse){
        try{

            if(empty($deleteInvestigatorFormRequest->reason)){
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($deleteInvestigatorFormRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $this->checkAuthorization($deleteInvestigatorFormRequest->currentUserId, $deleteInvestigatorFormRequest->visitId, $visitContext['state_quality_control']);

            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($deleteInvestigatorFormRequest->visitId);

            //Delete review
            $this->reviewRepositoryInterface->delete($investigatorFormEntity['id']);
            //Make investigator form not done
            $this->visitRepositoryInterface->updateInvestigatorFormStatus($investigatorFormEntity['visit_id'], Constants::INVESTIGATOR_FORM_NOT_DONE);
            //Reset QC if QC is needed in this visitType
            if($visitContext['visit_type']['qc_needed']) $this->visitRepositoryInterface->resetQc($deleteInvestigatorFormRequest->currentUserId);

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_code' => $visitContext['patient_code'],
                'id_review' => $investigatorFormEntity['id'],
                'reason' => $deleteInvestigatorFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $deleteInvestigatorFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $deleteInvestigatorFormRequest->visitId,
                Constants::TRACKER_DELETE_INVESTIGATOR_FORM,
                $actionDetails);

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(true,
                $investigatorFormEntity['user_id'],
                $studyName,
                $visitContext['patient_code'],
                $visitContext['visit_type']['name'] );

            $DeleteInvestigatorFormResponse->status = 200;
            $DeleteInvestigatorFormResponse->statusText =  'OK';

        } catch (GaelOException $e){

            $DeleteInvestigatorFormResponse->body = $e->getErrorBody();
            $DeleteInvestigatorFormResponse->status = $e->statusCode;
            $DeleteInvestigatorFormResponse->statusText =  $e->statusText;

        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitQcStatus){

        $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if ( ! $this->authorizationVisitService->isVisitAllowed() || in_array($visitQcStatus , [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])){
            throw new GaelOForbiddenException();
        }

    }
}

<?php

namespace App\GaelO\UseCases\UnlockInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationVisitService;
use App\GaelO\Services\MailServices;
use Exception;

class UnlockInvestigatorForm
{
    private AuthorizationVisitService $authorizationVisitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private MailServices $mailServices;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function execute(UnlockInvestigatorFormRequest $unlockInvestigatorFormRequest, UnlockInvestigatorFormResponse $unlockInvestigatorFormResponse)
    {

        try {

            if (empty($unlockInvestigatorFormRequest->reason)) {
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $visitContext = $this->visitRepositoryInterface->getVisitContext($unlockInvestigatorFormRequest->visitId);

            $studyName = $visitContext['visit_type']['visit_group']['study_name'];

            $this->checkAuthorization($unlockInvestigatorFormRequest->currentUserId, $unlockInvestigatorFormRequest->visitId, $visitContext['state_quality_control']);

            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($unlockInvestigatorFormRequest->visitId, false);

            if (!$investigatorFormEntity['validated']) {
                throw new GaelOBadRequestException('Form Already Unlocked');
            }

            //Unlock Investigator Form
            $this->reviewRepositoryInterface->unlockInvestigatorForm($unlockInvestigatorFormRequest->visitId);

            //Make investigator form not done
            $this->visitRepositoryInterface->updateInvestigatorFormStatus($unlockInvestigatorFormRequest->visitId, Constants::INVESTIGATOR_FORM_DRAFT);

            //Reset QC if QC is needed in this visitType
            if ($visitContext['visit_type']['qc_needed']) $this->visitRepositoryInterface->resetQc($unlockInvestigatorFormRequest->currentUserId);

            $actionDetails = [
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $investigatorFormEntity['id'],
                'reason' => $unlockInvestigatorFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $unlockInvestigatorFormRequest->currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $unlockInvestigatorFormRequest->visitId,
                Constants::TRACKER_UNLOCK_INVESTIGATOR_FORM,
                $actionDetails
            );

            //send unlock form notification to form owner
            $this->mailServices->sendUnlockFormMessage(
                $unlockInvestigatorFormRequest->visitId,
                true,
                $unlockInvestigatorFormRequest->currentUserId,
                $studyName,
                $visitContext['patient_id'],
                $visitContext['visit_type']['name']
            );

            $unlockInvestigatorFormResponse->status = 200;
            $unlockInvestigatorFormResponse->statusText =  'OK';
        } catch (GaelOException $e) {

            $unlockInvestigatorFormResponse->body = $e->getErrorBody();
            $unlockInvestigatorFormResponse->status = $e->statusCode;
            $unlockInvestigatorFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $currentUserId, int $visitId, string $visitQcStatus)
    {
        $this->authorizationVisitService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        $this->authorizationVisitService->setVisitId($visitId);
        if (!$this->authorizationVisitService->isVisitAllowed() || in_array($visitQcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])) {
            throw new GaelOForbiddenException();
        }
    }
}

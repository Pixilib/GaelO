<?php

namespace App\GaelO\UseCases\UnlockInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
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

            $studyName = $visitContext['patient']['study_name'];
            $visitId = $visitContext['id'];
            $currentUserId = $unlockInvestigatorFormRequest->currentUserId;

            if($unlockInvestigatorFormRequest->studyName !== $studyName){
                throw new GaelOForbiddenException("Should be called from the original study");
            }

            $this->checkAuthorization($currentUserId, $visitId, $visitContext['state_quality_control'], $studyName);

            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($visitId, false);

            if (!$investigatorFormEntity['validated']) {
                throw new GaelOBadRequestException('Form Already Unlocked');
            }

            //Unlock Investigator Form
            $this->reviewRepositoryInterface->unlockInvestigatorForm($visitId);

            //Make investigator form not done
            $this->visitRepositoryInterface->updateInvestigatorFormStatus($visitId, Constants::INVESTIGATOR_FORM_DRAFT);

            //Reset QC if QC is needed in this Visit
            if ($visitContext['state_quality_control'] !== Constants::QUALITY_CONTROL_NOT_NEEDED) $this->visitRepositoryInterface->resetQc($visitContext['id']);

            $actionDetails = [
                'visit_group_name' => $visitContext['visit_type']['visit_group']['name'],
                'visit_group_modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $investigatorFormEntity['id'],
                'reason' => $unlockInvestigatorFormRequest->reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_UNLOCK_INVESTIGATOR_FORM,
                $actionDetails
            );

            //send unlock form notification to form owner
            $this->mailServices->sendUnlockedFormMessage(
                $visitId,
                true,
                $currentUserId,
                $studyName,
                $visitContext['patient_id'],
                $visitContext['patient']['code'],
                $visitContext['visit_type']['name']
            );

            $unlockInvestigatorFormResponse->status = 200;
            $unlockInvestigatorFormResponse->statusText =  'OK';
        } catch (AbstractGaelOException $e) {
            $unlockInvestigatorFormResponse->body = $e->getErrorBody();
            $unlockInvestigatorFormResponse->status = $e->statusCode;
            $unlockInvestigatorFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function checkAuthorization(int $currentUserId, int $visitId, string $visitQcStatus, string $studyName)
    {
        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setStudyName($studyName);
        $this->authorizationVisitService->setVisitId($visitId);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR) || in_array($visitQcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])) {
            throw new GaelOForbiddenException();
        }
    }
}

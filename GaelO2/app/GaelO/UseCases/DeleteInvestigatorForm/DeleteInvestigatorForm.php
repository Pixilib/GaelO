<?php

namespace App\GaelO\UseCases\DeleteInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\TrackerRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationVisitService;
use App\GaelO\Services\FormService\InvestigatorFormService;
use App\GaelO\Services\MailServices;
use Exception;

class DeleteInvestigatorForm
{

    private AuthorizationVisitService $authorizationVisitService;
    private VisitRepositoryInterface $visitRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private InvestigatorFormService $inverstigatorFormService;
    private MailServices $mailServices;

    public function __construct(
        AuthorizationVisitService $authorizationVisitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        VisitRepositoryInterface $visitRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        InvestigatorFormService $inverstigatorFormService,
        MailServices $mailServices
    ) {
        $this->authorizationVisitService = $authorizationVisitService;
        $this->visitRepositoryInterface = $visitRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->inverstigatorFormService = $inverstigatorFormService;
        $this->mailServices = $mailServices;
    }

    public function execute(DeleteInvestigatorFormRequest $deleteInvestigatorFormRequest, DeleteInvestigatorFormResponse $DeleteInvestigatorFormResponse)
    {
        try {

            if (empty($deleteInvestigatorFormRequest->reason)) {
                throw new GaelOBadRequestException("Reason must be specified");
            }

            $visitId = $deleteInvestigatorFormRequest->visitId;
            $currentUserId = $deleteInvestigatorFormRequest->currentUserId;
            $reason = $deleteInvestigatorFormRequest->reason;

            $visitContext = $this->visitRepositoryInterface->getVisitContext($visitId);

            $studyName = $visitContext['patient']['study_name'];

            $investigatorFormEntity = $this->reviewRepositoryInterface->getInvestigatorForm($visitId, false);

            //check that it is called from the correct study
            if ($deleteInvestigatorFormRequest->studyName !== $studyName) {
                throw new GaelOForbiddenException("Should be called from same study than original study");
            }

            $this->checkAuthorization($currentUserId, $visitId, $visitContext['state_quality_control'], $studyName);

            $this->inverstigatorFormService->setCurrentUserId($currentUserId);
            $this->inverstigatorFormService->setVisitContextAndStudy($visitContext, $studyName);
            $this->inverstigatorFormService->deleteForm($investigatorFormEntity['id']);

            $actionDetails = [
                'visit_group_name' => $visitContext['visit_type']['visit_group']['name'],
                'modality' => $visitContext['visit_type']['visit_group']['modality'],
                'visit_type' => $visitContext['visit_type']['name'],
                'patient_id' => $visitContext['patient_id'],
                'id_review' => $investigatorFormEntity['id'],
                'reason' => $reason
            ];

            $this->trackerRepositoryInterface->writeAction(
                $currentUserId,
                Constants::ROLE_SUPERVISOR,
                $studyName,
                $visitId,
                Constants::TRACKER_DELETE_INVESTIGATOR_FORM,
                $actionDetails
            );

            //send Email notification to review owner
            $this->mailServices->sendDeleteFormMessage(
                $visitId,
                true,
                $investigatorFormEntity['user_id'],
                $studyName,
                $visitContext['patient_id'],
                $visitContext['patient']['code'],
                $visitContext['visit_type']['name']
            );

            $DeleteInvestigatorFormResponse->status = 200;
            $DeleteInvestigatorFormResponse->statusText =  'OK';
        } catch (AbstractGaelOException $e) {

            $DeleteInvestigatorFormResponse->body = $e->getErrorBody();
            $DeleteInvestigatorFormResponse->status = $e->statusCode;
            $DeleteInvestigatorFormResponse->statusText =  $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $visitQcStatus, string $studyName)
    {

        $this->authorizationVisitService->setUserId($currentUserId);
        $this->authorizationVisitService->setVisitId($visitId);
        $this->authorizationVisitService->setStudyName($studyName);

        if (!$this->authorizationVisitService->isVisitAllowed(Constants::ROLE_SUPERVISOR) || in_array($visitQcStatus, [Constants::QUALITY_CONTROL_ACCEPTED, Constants::QUALITY_CONTROL_REFUSED])) {
            throw new GaelOForbiddenException();
        }
    }
}

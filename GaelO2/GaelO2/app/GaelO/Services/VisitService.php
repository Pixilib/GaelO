<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private MailServices $mailServices;
    private ReviewStatusRepository $reviewStatusRepository;

    private int $visitId;

    public function __construct(
        VisitRepository $visitRepository,
        ReviewRepository $reviewRepository,
        ReviewStatusRepository $reviewStatusRepository,
        MailServices $mailServices
    ) {
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
        $this->reviewStatusRepository = $reviewStatusRepository;
        $this->reviewRepository = $reviewRepository;
    }

    public function setVisitId(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function getVisitContext(): array
    {
        return $this->visitRepository->getVisitContext($this->visitId);
    }

    public function updateUploadStatus(string $uploadStatus)
    {

        if($uploadStatus === Constants::UPLOAD_STATUS_NOT_DONE){
            $visitContext = $this->visitRepository->getVisitContext($this->visitId);
            if($visitContext['state_investigator_form'] === Constants::INVESTIGATOR_FORM_DONE) {
                $this->reviewRepository->unlockInvestigatorForm($this->visitId);
                $this->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
            }

        }

        $updatedEntity = $this->visitRepository->updateUploadStatus($this->visitId, $uploadStatus);

        if (
            $updatedEntity['upload_status'] === Constants::UPLOAD_STATUS_DONE
            && ($updatedEntity['state_investigator_form'] === Constants::INVESTIGATOR_FORM_NOT_NEEDED || $updatedEntity['state_investigator_form'] === Constants::INVESTIGATOR_FORM_DONE)
        ) {
            $this->sendUploadEmailAndSkipQcIfNeeded($this->visitId);
        }
    }

    public function updateInvestigatorFormStatus(string $stateInvestigatorForm)
    {
        $updatedEntity = $this->visitRepository->updateInvestigatorFormStatus($this->visitId, $stateInvestigatorForm);
        if (
            $updatedEntity['upload_status'] === Constants::UPLOAD_STATUS_DONE
            && $updatedEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_DONE
        ) {
            $this->sendUploadEmailAndSkipQcIfNeeded($this->visitId);
        }
    }

    private function sendUploadEmailAndSkipQcIfNeeded()
    {
        //If uploaded done and investigator done (Done or Not Needed) send notification message
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);

        $patientId = $visitEntity['patient_id'];
        $visitType = $visitEntity['visit_type']['name'];
        $studyName = $visitEntity['patient']['study_name'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $qcNeeded = $visitEntity['state_quality_control'] !== Constants::QUALITY_CONTROL_NOT_NEEDED;
        $reviewNeeded = $reviewStatus['review_status'] !== Constants::REVIEW_STATUS_NOT_NEEDED;

        $this->mailServices->sendUploadedVisitMessage($this->visitId, $visitEntity['creator_user_id'], $studyName, $patientId, $visitType, $qcNeeded);
        //If Qc NotNeeded mark visit as available for review
        if (!$qcNeeded && $reviewNeeded ) {
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, true);
            $this->mailServices->sendAvailableReviewMessage($this->visitId, $studyName, $patientId, $visitType);
        }
    }


    public function editQc(string $stateQc, int $controllerId, bool $imageQc, bool $formQc, ?string $imageQcComment, ?string $formQcComment)
    {
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $reviewNeeded = $reviewStatus['review_status'] !== Constants::REVIEW_STATUS_NOT_NEEDED;
        $localFormNeeded = $visitEntity['state_investigator_form'] !== Constants::INVESTIGATOR_FORM_NOT_NEEDED;

        $this->visitRepository->editQc($this->visitId, $stateQc, $controllerId, $imageQc, $formQc, $imageQcComment, $formQcComment);

        if ($stateQc === Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED && $localFormNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewRepository->unlockInvestigatorForm($this->visitId);
            $this->visitRepository->updateInvestigatorFormStatus($this->visitId, Constants::INVESTIGATOR_FORM_DRAFT);
        }

        if ($stateQc === Constants::QUALITY_CONTROL_ACCEPTED && $reviewNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName , true);
        }
    }

    public function resetQc(): void
    {
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];
        $this->visitRepository->resetQc($this->visitId);
        $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName , false);
    }

    public function getReviewStatus(string $studyName)
    {
        return $this->reviewStatusRepository->getReviewStatus($this->visitId, $studyName);
    }

}

<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\GaelO\Interfaces\Adapters\JobInterface;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\ReviewStatusRepository;
use App\GaelO\Repositories\VisitRepository;

class VisitService
{
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private MailServices $mailServices;
    private ReviewStatusRepository $reviewStatusRepository;
    private JobInterface $jobInterface;

    private int $visitId;

    public function __construct(
        VisitRepository $visitRepository,
        ReviewRepository $reviewRepository,
        ReviewStatusRepository $reviewStatusRepository,
        MailServices $mailServices,
        JobInterface $jobInterface
    ) {
        $this->visitRepository = $visitRepository;
        $this->mailServices = $mailServices;
        $this->reviewStatusRepository = $reviewStatusRepository;
        $this->reviewRepository = $reviewRepository;
        $this->jobInterface = $jobInterface;
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

        if($uploadStatus === UploadStatusEnum::NOT_DONE->value){
            $visitContext = $this->visitRepository->getVisitContext($this->visitId);
            if($visitContext['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value) {
                $this->reviewRepository->unlockInvestigatorForm($this->visitId);
                $this->updateInvestigatorFormStatus(InvestigatorFormStateEnum::DRAFT->value);
            }

        }

        $updatedEntity = $this->visitRepository->updateUploadStatus($this->visitId, $uploadStatus);

        if (
            $updatedEntity['upload_status'] === UploadStatusEnum::DONE->value
            && ($updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::NOT_NEEDED->value || $updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value)
        ) {
            $this->sendUploadEmailAndSkipQcIfNeeded($this->visitId);
        }
    }

    public function updateInvestigatorFormStatus(string $stateInvestigatorForm)
    {
        $updatedEntity = $this->visitRepository->updateInvestigatorFormStatus($this->visitId, $stateInvestigatorForm);
        if (
            $updatedEntity['upload_status'] === UploadStatusEnum::DONE->value
            && ($updatedEntity['state_investigator_form'] === InvestigatorFormStateEnum::DONE->value)
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
        $patientCode = $visitEntity['patient']['code'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $qcNeeded = $visitEntity['state_quality_control'] !== QualityControlStateEnum::NOT_NEEDED->value;
        $reviewNeeded = $reviewStatus['review_status'] !== ReviewStatusEnum::NOT_NEEDED->value;

        $this->mailServices->sendUploadedVisitMessage($this->visitId, $visitEntity['creator_user_id'], $studyName, $patientId, $patientCode, $visitType, $qcNeeded);
        // Send auto qc job
        if($qcNeeded){
            $this->jobInterface->sendQcReportJob($this->visitId);
        }
        //If Qc NotNeeded mark visit as available for review
        if (!$qcNeeded && $reviewNeeded ) {
            $this->reviewStatusRepository->updateReviewAvailability($this->visitId, $studyName, true);
            $this->mailServices->sendReviewReadyMessage($this->visitId, $studyName, $patientId, $patientCode, $visitType);
        }
    }


    public function editQc(string $stateQc, int $controllerId, ?bool $imageQc, ?bool $formQc, ?string $imageQcComment, ?string $formQcComment)
    {
        $visitEntity = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitEntity['patient']['study_name'];

        $reviewStatus = $this->getReviewStatus($studyName);

        $reviewNeeded = $reviewStatus['review_status'] !== ReviewStatusEnum::NOT_NEEDED->value;
        $localFormNeeded = $visitEntity['state_investigator_form'] !== InvestigatorFormStateEnum::NOT_NEEDED->value;

        $this->visitRepository->editQc($this->visitId, $stateQc, $controllerId, $imageQc, $formQc, $imageQcComment, $formQcComment);

        if ($stateQc === QualityControlStateEnum::CORRECTIVE_ACTION_ASKED->value && $localFormNeeded) {
            //Invalidate invistagator form and set it status as draft in the visit
            $this->reviewRepository->unlockInvestigatorForm($this->visitId);
            $this->visitRepository->updateInvestigatorFormStatus($this->visitId, InvestigatorFormStateEnum::DRAFT->value);
        }

        if ($stateQc === QualityControlStateEnum::ACCEPTED->value && $reviewNeeded) {
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

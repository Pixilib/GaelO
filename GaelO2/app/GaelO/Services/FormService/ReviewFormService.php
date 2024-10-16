<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\Repositories\StudyRepositoryInterface;
use App\GaelO\Services\FormService\FormService;
use App\GaelO\Services\GaelOStudiesService\AbstractGaelOStudy;
use App\GaelO\Services\GaelOStudiesService\Events\AwaitingAdjudicationEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitConcludedEvent;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

class ReviewFormService extends FormService
{

    protected ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    protected bool $local = false;

    public function __construct(
        VisitService $visitService,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface,
        StudyRepositoryInterface $studyRepositoryInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface
    ) {
        parent::__construct($studyRepositoryInterface, $reviewRepositoryInterface, $visitService, $mailServices, $frameworkInterface);
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
    }

    public function createForm(array $data, bool $validated, ?bool $adjudication = null): int
    {
        $this->abstractVisitRules->setFormData($data);
        $this->abstractVisitRules->setAdjudication($adjudication);
        $validity = $this->abstractVisitRules->checkReviewFormValidity($validated);
        if (!$validity) {
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        $createdReviewId = $this->reviewRepositoryInterface->createReview(false, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated) {
            $this->doSpecificReviewDecisions();
        }
        return $createdReviewId;
    }

    public function updateForm(int $reviewId, array $uploadedFileKeys, array $data, bool $validated)
    {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        //Pass validation
        $this->abstractVisitRules->setFormData($data);
        $this->abstractVisitRules->setUploadedFileKeys($uploadedFileKeys);
        $this->abstractVisitRules->setAdjudication($reviewEntity['adjudication']);
        $validity = $this->abstractVisitRules->checkReviewFormValidity($validated);
        if (!$validity) {
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        //Update DB
        $this->reviewRepositoryInterface->updateReview($reviewId, $this->currentUserId, $data, $validated);
        if ($validated) {
            $this->doSpecificReviewDecisions();
        }
    }

    public function deleteForm(int $reviewId)
    {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        $this->abstractVisitRules->setAdjudication($reviewEntity['adjudication']);
        $this->reviewRepositoryInterface->delete($reviewId);
        $this->doSpecificReviewDecisions();
    }

    public function unlockForm(int $reviewId)
    {
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        $this->abstractVisitRules->setAdjudication($reviewEntity['adjudication']);
        $this->reviewRepositoryInterface->unlockReview($reviewId);
        $this->doSpecificReviewDecisions();
    }

    private function doSpecificReviewDecisions()
    {
        $visitDecision = $this->abstractVisitRules->getVisitDecisionObject();
        $reviewStatus = $visitDecision->getReviewStatus();
        $availability = $visitDecision->getReviewAvailability($reviewStatus);
        $conclusion = $visitDecision->getReviewConclusion();
        $targetLesions = null;

        if ($reviewStatus === ReviewStatusEnum::DONE->value) {
            $targetLesions = $visitDecision->getTargetLesion();
        }

        if ($reviewStatus === ReviewStatusEnum::NOT_DONE->value && $conclusion !== null) {
            throw new GaelOException("Review Status Not Done needs to be associated with null conclusion value");
        }
        //Update review status
        $this->reviewStatusRepositoryInterface->updateReviewAvailabilityStatusAndConclusion($this->visitId, $this->studyName, $availability, $reviewStatus, $conclusion, $targetLesions);

        //Send Notification emails
        if ($reviewStatus === ReviewStatusEnum::WAIT_ADJUDICATION->value) {
            $awaitingAdjudicationEvent = new AwaitingAdjudicationEvent($this->visitContext);
            $studyObject = AbstractGaelOStudy::getSpecificStudyObject($this->studyName);
            $studyObject->onEventStudy($awaitingAdjudicationEvent);
        } else if ($reviewStatus === ReviewStatusEnum::DONE->value) {
            //In case of conclusion reached send conclusion (but not to uploader if ancillary study)
            $visitConcludedEvent = new VisitConcludedEvent($this->visitContext);
            $visitConcludedEvent->setConclusion($conclusion);
            $visitConcludedEvent->setUploaderUserId($this->studyEntity->isAncillaryStudy() ? null : $this->uploaderId);
            $studyObject = AbstractGaelOStudy::getSpecificStudyObject($this->studyName);
            $studyObject->onEventStudy($visitConcludedEvent);
        }
    }
}

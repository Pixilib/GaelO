<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\ReviewStatusRepositoryInterface;
use App\GaelO\Services\FormService\FormService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

class ReviewFormService extends FormService
{

    protected ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;

    public function __construct(
        VisitService $visitService,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface
    ) {
        parent::__construct($reviewRepositoryInterface, $visitService, $mailServices, $frameworkInterface);
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
    }

    public function setReviewStatus(array $reviewStatusEntity)
    {
        $this->reviewStatusEntity = $reviewStatusEntity;
    }

    public function saveReview(array $data, bool $validated, bool $adjudication): int
    {
        $this->abstractVisitRules->setFormData($data);
        $validity = $this->abstractVisitRules->checkReviewFormValidity($validated, $adjudication);
        if (!$validity) {
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        $createdReviewId = $this->reviewRepositoryInterface->createReview(false, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated && $this->reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions();
        }
        return $createdReviewId;
    }

    public function updateReview(int $reviewId, array $data, bool $validated): void
    {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        //Pass validation
        $this->abstractVisitRules->setFormData($data);
        $validity = $this->abstractVisitRules->checkReviewFormValidity($validated, $reviewEntity['adjudication']);
        if (!$validity) {
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        //Update DB
        $this->reviewRepositoryInterface->updateReview($reviewId, $this->currentUserId, $data, $validated);
        if ($validated && $this->reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions();
        }
    }

    public function deleteReview(int $reviewId): void
    {
        $this->reviewRepositoryInterface->delete($reviewId);
        $this->doSpecificReviewDecisions();
    }

    public function unlockReview(int $reviewId): void
    {
        $this->reviewRepositoryInterface->unlockReview($reviewId);
        $this->doSpecificReviewDecisions();
    }

    private function doSpecificReviewDecisions()
    {
        $reviewStatus = $this->abstractVisitRules->getReviewStatus();
        $availability = $this->abstractVisitRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->abstractVisitRules->getReviewConclusion();
        $targetLesions = $this->abstractVisitRules->getTargetLesion();

        if ($availability !== $this->reviewStatusEntity['review_available']) $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability);
        if ($reviewStatus !== $this->reviewStatusEntity['review_status']) $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $this->studyName, $reviewStatus);
        if ($conclusion === Constants::REVIEW_STATUS_DONE) $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $this->studyName, $conclusion, $targetLesions);

        //Send Notification emails
        if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            $this->mailServices->sendAwaitingAdjudicationMessage($this->studyName, $this->patientId,  $this->visitType, $this->visitId);
        } else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //SK Si ANCIllaire pas besoin d'embetter l'uploader du princeps ...
            $this->mailServices->sendVisitConcludedMessage(
                $this->visitId,
                $this->uploaderId,
                $this->studyName,
                $this->patientId,
                $this->visitType,
                $conclusion
            );
        }
    }
}

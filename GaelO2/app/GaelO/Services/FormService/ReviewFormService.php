<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
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
        $this->local = false;
    }

    public function saveReview(array $data, bool $validated, bool $adjudication): int
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

    public function updateReview(int $reviewId, array $data, bool $validated): void
    {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        //Pass validation
        $this->abstractVisitRules->setFormData($data);
        $this->abstractVisitRules->setAdjudication($reviewEntity['adjudication']);
        $validity = $this->abstractVisitRules->checkReviewFormValidity($validated, $reviewEntity['adjudication']);
        if (!$validity) {
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        //Update DB
        $this->reviewRepositoryInterface->updateReview($reviewId, $this->currentUserId, $data, $validated);
        if ($validated) {
            $this->doSpecificReviewDecisions();
        }
    }

    public function deleteReview(int $reviewId): void
    {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        $this->abstractVisitRules->setAdjudication($reviewEntity['adjudication']);
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
        $targetLesions = null;

        if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            $targetLesions = $this->abstractVisitRules->getTargetLesion();
        }

        if ($reviewStatus === Constants::REVIEW_STATUS_NOT_DONE && $conclusion !== null) {
            throw new GaelOException("Review Status Not Done needs to be associated with null conclusion value");
        }
        //Update review availability if change compared on current value
        if ($availability !== $this->visitContext['review_status']['review_available']) $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability);
        //Update review status table to computed new values
        //sk a fusionner avec rev availability
        $this->reviewStatusRepositoryInterface->updateReviewStatusAndConclusion($this->visitId, $this->studyName, $reviewStatus, $conclusion, $targetLesions);

        //Send Notification emails
        if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            $this->mailServices->sendAwaitingAdjudicationMessage($this->studyName, $this->patientId, $this->patientCode,  $this->visitType, $this->visitId);
        } else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //SK Si ANCIllaire pas besoin d'embetter l'uploader du princeps ...
            $this->mailServices->sendVisitConcludedMessage(
                $this->visitId,
                $this->uploaderId,
                $this->studyName,
                $this->patientId,
                $this->patientCode,
                $this->visitType,
                $conclusion
            );
        }
    }
    
}

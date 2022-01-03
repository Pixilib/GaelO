<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;

class ReviewFormService extends FormService {

    public function setReviewStatus(array $reviewStatusEntity){
        $this->reviewStatusEntity = $reviewStatusEntity;
    }

    public function saveReview(array $data, bool $validated, bool $adjudication) : int {
        $validity = $this->abstractVisitRules->checkReviewFormValidity($data, $validated, $adjudication);
        if(!$validity){
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        $createdReviewId = $this->reviewRepositoryInterface->createReview(false, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated && $this->reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions();
        }
        return $createdReviewId;
    }

    public function updateReview(int $reviewId, array $data, bool $validated) : void {
        //Get current Entity to know if adjudication form
        $reviewEntity = $this->reviewRepositoryInterface->find($reviewId);
        //Pass validation
        $validity = $this->abstractVisitRules->checkReviewFormValidity($data, $validated, $reviewEntity['adjudication']);
        if(!$validity){
            throw new GaelOBadRequestException('Review Form Validation Failed');
        }
        //Update DB
        $this->reviewRepositoryInterface->updateReview($reviewId, $this->currentUserId, $data, $validated);
        if ($validated && $this->reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions();
		}
    }

    public function deleteReview(int $reviewId) : void {
        $this->reviewRepositoryInterface->delete($reviewId);
        $this->doSpecificReviewDecisions();
    }

    public function unlockReview(int $reviewId) : void {
        $this->reviewRepositoryInterface->unlockReview($reviewId);
        $this->doSpecificReviewDecisions();
    }

    private function doSpecificReviewDecisions(){
        $reviewStatus = $this->abstractVisitRules->getReviewStatus();
        $availability = $this->abstractVisitRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->abstractVisitRules->getReviewConclusion();

        if( $availability !== $this->reviewStatusEntity['review_available'] ) $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability );
        if( $reviewStatus !== $this->reviewStatusEntity['review_status'] ) $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $this->studyName, $reviewStatus );
        if( $conclusion === Constants::REVIEW_STATUS_DONE ) $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $this->studyName, $conclusion );

        //Send Notification emails
		if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            $this->mailServices->sendAwaitingAdjudicationMessage($this->studyName, $this->patientId,  $this->visitType, $this->visitId);
		}else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //Si ANCIllaire pas besoin d'embetter l'uploader du princepts ...
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

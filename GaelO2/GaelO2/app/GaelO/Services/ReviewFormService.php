<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;
use App\GaelO\Services\VisitService;

class ReviewFormService {

    private VisitService $visitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private AbstractStudyRules $abstractStudyRules;
    private MailServices $mailServices;

    private int $currentUserId;
    private int $visitId;
    private array $visitContext;
    private string $studyName;
    private string $visitType;
    private int $patientCode;
    private int $uploaderId;


    //SK Reste à gérer les Files

    public function __construct(VisitService $visitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        MailServices $mailServices
        )
    {
        $this->visitService = $visitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function setCurrentUserId(int $currentUserId){
        $this->currentUserId = $currentUserId;
    }

    public function setVisitContextAndStudy(array $visitContext, string $studyName){

        $this->visitId = $visitContext['id'];
        $this->visitService->setVisitId($visitContext['id']);
        $this->visitContext = $visitContext;
        $this->visitType = $this->visitContext['visit_type']['name'];
        $this->patientCode = $this->visitContext['patient_code'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->studyName = $studyName;
        $modality = $this->visitContext['visit_type']['visit_group']['modality'];
        $this->abstractStudyRules = LaravelFunctionAdapter::make('\App\GaelO\Services\SpecificStudiesRules\\'.$this->studyName.'_'.$modality.'_'.$this->visitType);

    }

    public function setReviewStatus(array $reviewStatusEntity){
        $this->reviewStatusEntity = $reviewStatusEntity;
    }

    public function saveReview(array $data, bool $validated, bool $adjudication) : int {
        $validity = $this->abstractStudyRules->checkReviewFormValidity($data, $validated, $adjudication);
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
        $validity = $this->abstractStudyRules->checkReviewFormValidity($data, $validated, $reviewEntity['adjudication']);
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
        $reviewStatus = $this->abstractStudyRules->getReviewStatus();
        $availability = $this->abstractStudyRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->abstractStudyRules->getReviewConclusion();

        if( $availability !== $this->reviewStatusEntity['review_available'] ) $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability );
        if( $reviewStatus !== $this->reviewStatusEntity['review_status'] ) $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $this->studyName, $reviewStatus );
        if( $conclusion === Constants::REVIEW_STATUS_DONE ) $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $this->studyName, $conclusion );

        //Send Notification emails
		if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            $this->mailServices->sendAwaitingAdjudicationMessage($this->studyName, $this->patientCode,  $this->visitType, $this->visitId);
		}else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //Si ANCIllaire pas besoin d'embetter l'uploader du princepts ...
            $this->mailServices->sendVisitConcludedMessage(
                $this->uploaderId,
                $this->studyName,
                $this->patientCode,
                $this->visitType,
                $conclusion
            );
        }

    }



}

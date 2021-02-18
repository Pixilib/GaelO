<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;
use App\GaelO\Services\VisitService;
use Illuminate\Support\Facades\App;

class ReviewFormService {

    private VisitService $visitService;
    private VisitRepository $visitRepository;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private AbstractStudyRules $abstractStudyRules;
    private MailServices $mailServices;

    private int $currentUserId;
    private int $visitId;
    private array $visitContext;
    private string $studyName;
    private string $visitType;
    private int $patientCode;
    private int $uploaderId;


    //SK Les Files ne sont pas gérés

    public function __construct(VisitService $visitService,
        VisitRepository $visitRepository,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
        )
    {
        $this->visitService = $visitService;
        $this->visitRepository = $visitRepository;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function setCurrentUserId(int $currentUserId){
        $this->currentUserId = $$currentUserId;
    }

    public function setCurrentVisitId(int $visitId){
        $this->visitId = $visitId;
        $this->visitService->setVisitId($visitId);
        $this->visitContext = $this->visitRepository->getVisitContext($this->visitId);
        $modality = $this->visitContext['visit_type']['visit_group']['modality'];
        $this->studyName = $this->visitContext['visit_type']['visit_group']['study_name'];
        $this->visitType = $this->visitContext['visit_type']['name'];
        $this->patientCode = $this->visitContext['patient_code'];
        $this->uploaderId = $this->visitContext['creator_user_id'];
        $this->abstractStudyRules = LaravelFunctionAdapter::make('\App\GaelO\Services\SpecificStudiesRules\\'.$this->studyName.'_'.$modality.'_'.$this->visitType);
        //SK ICI INSTANCER LA CLASSE SPECIFIQUE QUI IMPLEMENTE L INTERFACE STUDY RULES ?
    }

    public function saveReviewData(array $data, bool $validated, bool $adjudication){
        $reviewStatusEntity = $this->visitService->getReviewStatus($this->studyName);
        $this->reviewRepositoryInterface->createReview(false, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated && $reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions($data);
		}
    }

    public function updateReviewData(array $data, bool $validated){
        $reviewStatusEntity = $this->visitService->getReviewStatus($this->studyName);
        $reviewEntity = $this->reviewRepositoryInterface->getReviewFormForStudyVisitUser($this->studyName, $this->visitId, $this->currentUserId );
        $this->reviewRepositoryInterface->updateReview($reviewEntity['id'], $this->currentUserId, $data, $validated);
        if ($validated && $reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions($data);
		}
    }

    private function doSpecificReviewDecisions(array $data){
        $reviewStatus = $this->abstractStudyRules->getReviewStatus();
        $availability = $this->abstractStudyRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->abstractStudyRules->getReviewConclusion();
        $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability );
        $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $this->studyName, $conclusion );
        $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $this->studyName, $reviewStatus );

        //Send Notification emails
		if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            $this->mailServices->sendAwaitingAdjudicationMessage($this->studyName, $this->patientCode,  $this->visitType, $this->visitId);
		}else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            $this->mailServices->sendVisitConcludedMessage(
                $this->uploaderId,
                $this->studyName,
                $this->patientCode,
                $this->visitType,
                $conclusion
            );
        }

        $actionDetails = [
            'raw_data' => $data
        ];

        $this->trackerRepositoryInterface->writeAction($this->currentUserId, Constants::ROLE_REVIEWER, $this->studyName, $this->visitId, Constants::TRACKER_SAVE_REVIEWER_FORM, $actionDetails);

    }



}

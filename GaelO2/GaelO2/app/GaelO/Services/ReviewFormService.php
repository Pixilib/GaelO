<?php

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\InterfaceStudyRules;
use App\GaelO\Services\VisitService;

class ReviewFormService {

    private VisitService $visitService;
    private VisitRepository $visitRepository;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private InterfaceStudyRules $interfaceStudyRules;
    private MailServices $mailServices;

    private int $currentUserId;
    private int $visitId;

    //SK Les Files ne sont pas gérés
    //Tracker a mettre dans tous les cas
    //Mail a faire

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
        $this->studyName = $this->visitContext['visit_type']['visit_group']['study_name'];
        //SK ICI INSTANCER LA CLASSE SPECIFIQUE QUI IMPLEMENTE L INTERFACE STUDY RULES ?
    }

    public function saveReviewData(array $data, bool $validated, bool $adjudication){
        $reviewStatusEntity = $this->visitService->getReviewStatus($this->studyName);
        $this->reviewRepositoryInterface->createReview(false, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated && $reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions($this->studyName);
		}
    }

    public function updateReviewData(array $data, bool $validated){
        $reviewStatusEntity = $this->visitService->getReviewStatus($this->studyName);
        $reviewEntity = $this->reviewRepositoryInterface->getReviewFromForStudyVisitUser($this->studyName, $this->visitId, $this->currentUserId );
        $this->reviewRepositoryInterface->updateReview($reviewEntity['id'], $this->currentUserId, $data, $validated);
        if ($validated && $reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions($data);
		}
    }

    private function doSpecificReviewDecisions(array $data){
        $reviewStatus = $this->interfaceStudyRules->getReviewStatus();
        $availability = $this->interfaceStudyRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->interfaceStudyRules->getReviewConclusion();
        $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $this->studyName, $availability );
        $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $this->studyName, $conclusion );
        $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $this->studyName, $reviewStatus );

        //Send Notification emails
		if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            //SK A FAIRE
            //$this->mailServices->sendAwaitingAdjudicationMessage()


		}else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //SK A FAIRE
            //$this->mailServices->sendVisitConcludedMessage();

        }

        $actionDetails = [
            'raw_data' => $data
        ];

        $this->trackerRepositoryInterface->writeAction($this->currentUserId, Constants::ROLE_REVIEWER, $this->studyName, $this->visitId, Constants::TRACKER_SAVE_REVIEWER_FORM, $actionDetails);

    }



}

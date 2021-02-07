<?php

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\InterfaceStudiesRules;
use App\GaelO\Services\SpecificStudiesRules\InterfaceStudyRules;
use App\GaelO\Services\VisitService;

class ReviewService {

    private VisitService $visitService;
    private VisitRepository $visitRepository;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private InterfaceStudyRules $interfaceStudyRules;
    private MailServices $mailServices;

    private int $currentUserId;
    private int $visitId;

    //SK L UPDATE N EST PAS GERE
    //SK Les FIles ne sont pas gérés
    //Tracker a mettre dans tous les cas
    //Mail a finir

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
        //SK ICI INSTANCER LA CLASSE SPECIFIQUE QUI IMPLEMENTE L INTERFACE STUDY RULES ?
    }

    public function saveLocalData(array $data, bool $validated){
        $visitContext = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitContext['visit_type']['visit_group']['study_name'];
        $this->reviewRepositoryInterface->createReview(true, $visitContext['id'], $studyName, $this->currentUserId, $data, $validated);
        if ($validated) {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DONE);
        }else {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
        }

        $actionDetails = [
            'raw_data'=>json_encode($data)
        ];

        $this->trackerRepositoryInterface->writeAction($this->currentUserId, Constants::ROLE_INVESTIGATOR, $studyName, $this->visitId, Constants::TRACKER_SAVE_INVESTIGATOR_FORM, $actionDetails);

    }

    public function saveReviewData(string $studyName, array $data, bool $validated, bool $adjudication){
        $reviewStatusEntity = $this->visitService->getReviewStatus($studyName);
        $this->reviewRepositoryInterface->createReview(false, $this->visitId, $studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated && $reviewStatusEntity['review_status'] !== Constants::REVIEW_STATUS_DONE) {
            $this->doSpecificReviewDecisions($studyName);
		}
    }

    private function doSpecificReviewDecisions($studyName){
        $reviewStatus = $this->interfaceStudyRules->getReviewStatus();
        $availability = $this->interfaceStudyRules->getReviewAvailability($reviewStatus);
        $conclusion = $this->interfaceStudyRules->getReviewConclusion();
        $this->reviewStatusRepositoryInterface->updateReviewAvailability($this->visitId, $studyName, $availability );
        $this->reviewStatusRepositoryInterface->updateReviewConclusion($this->visitId, $studyName, $conclusion );
        $this->reviewStatusRepositoryInterface->updateReviewStatus($this->visitId, $studyName, $reviewStatus );

        //Send Notification emails
		if ($reviewStatus === Constants::REVIEW_STATUS_WAIT_ADJUDICATION) {
            //SK A FAIRE
            //$this->mailServices->sendAwaitingAdjudicationMessage()


		}else if ($reviewStatus === Constants::REVIEW_STATUS_DONE) {
            //SK A FAIRE
            //$this->mailServices->sendVisitConcludedMessage();

        }

    }



}

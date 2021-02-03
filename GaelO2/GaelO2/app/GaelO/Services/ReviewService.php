<?php

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\ReviewRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\VisitService;

class ReviewService {

    private VisitService $visitService;
    private VisitRepository $visitRepository;
    private ReviewRepository $reviewRepository;
    private TrackerRepositoryInterface $trackerRepositoryInterface;

    private int $currentUserId;
    private int $visitId;

    public function __construct(VisitService $visitService, VisitRepository $visitRepository, ReviewRepository $reviewRepository, TrackerRepositoryInterface $trackerRepositoryInterface)
    {
        $this->visitService = $visitService;
        $this->visitRepository = $visitRepository;
        $this->reviewRepository = $reviewRepository;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
    }

    public function setCurrentUserId(int $currentUserId){
        $this->currentUserId = $$currentUserId;
    }

    public function setCurrentVisitId(int $visitId){
        $this->visitId = $visitId;
        $this->visitService->setVisitId($visitId);
    }

    public function saveLocalData(array $data, bool $validated){
        $visitContext = $this->visitRepository->getVisitContext($this->visitId);
        $studyName = $visitContext['visit_type']['visit_group']['study_name'];
        $this->reviewRepository->createReview(true, $visitContext['id'], $studyName, $this->currentUserId, $data, $validated);
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

        $this->reviewRepository->createReview(false, $this->visitId, $studyName, $this->currentUserId, $data, $validated, $adjudication);
        if ($validated) {
			//$this->setVisitValidation();
		}
    }


}

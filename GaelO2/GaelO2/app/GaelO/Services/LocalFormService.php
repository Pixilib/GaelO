<?php

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

class LocalFormService {

    private VisitService $visitService;
    private VisitRepository $visitRepository;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private TrackerRepositoryInterface $trackerRepositoryInterface;
    private int $currentUserId;
    private int $visitId;

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
    }


    public function saveLocalData(array $data, bool $validated){

        $this->reviewRepositoryInterface->createReview(true, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated);
        $this->sendEmailAndWriteTracker($validated, $data);

    }

    public function updateLocalData(array $data, bool $validated){
        $localReviewEntitity = $this->reviewRepositoryInterface->getInvestigatorForm($this->visitId);
        $this->reviewRepositoryInterface->updateReview($localReviewEntitity['id'], $this->currentUserId, $data, $validated);
        $this->sendEmailAndWriteTracker($validated, $data);
    }

    private function sendEmailAndWriteTracker($validated, $data){
        if ($validated) {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DONE);
        }else {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
        }

        $actionDetails = [
            'raw_data' => $data
        ];

        $this->trackerRepositoryInterface->writeAction($this->currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName, $this->visitId, Constants::TRACKER_SAVE_INVESTIGATOR_FORM, $actionDetails);
    }

}

<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\ReviewRepositoryInterface;
use App\GaelO\Interfaces\ReviewStatusRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\SpecificStudiesRules\AbstractStudyRules;
use App\GaelO\Services\VisitService;

class InvestigatorFormService {

    private VisitService $visitService;
    private ReviewRepositoryInterface $reviewRepositoryInterface;
    private AbstractStudyRules $abstractStudyRules;
    private int $currentUserId;
    private int $visitId;

    public function __construct(VisitService $visitService,
        ReviewRepositoryInterface $reviewRepositoryInterface,
        ReviewStatusRepositoryInterface $reviewStatusRepositoryInterface,
        TrackerRepositoryInterface $trackerRepositoryInterface,
        MailServices $mailServices
        )
    {
        $this->visitService = $visitService;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
        $this->reviewStatusRepositoryInterface = $reviewStatusRepositoryInterface;
        $this->trackerRepositoryInterface = $trackerRepositoryInterface;
        $this->mailServices = $mailServices;
    }

    public function setCurrentUserId(int $currentUserId) : void {
        $this->currentUserId = $currentUserId;
    }

    public function setVisitContext(array $visitContext) : void {
        $this->visitId = $visitContext['id'];
        $this->visitService->setVisitId($visitContext['id']);
        $modality = $visitContext['visit_type']['visit_group']['modality'];
        $this->visitType = $visitContext['visit_type']['name'];
        $this->studyName = $visitContext['visit_type']['visit_group']['study_name'];
        $this->abstractStudyRules = LaravelFunctionAdapter::make('\App\GaelO\Services\SpecificStudiesRules\\'.$this->studyName.'_'.$modality.'_'.$this->visitType);

    }


    public function saveInvestigatorForm(array $data, bool $validated) : void {
        if( ! $this->abstractStudyRules->checkInvestigatorFormValidity($data, $validated)) throw new GaelOBadRequestException('Form Contraints Failed');
        $this->reviewRepositoryInterface->createReview(true, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated);
        $this->updateVisitInvestigatorFormStatus($validated);

    }

    public function updateInvestigatorForm(array $data, bool $validated) : void {
        if( ! $this->abstractStudyRules->checkInvestigatorFormValidity($data, $validated) ) throw new GaelOBadRequestException('Form Contraints Failed');;
        $localReviewEntitity = $this->reviewRepositoryInterface->getInvestigatorForm($this->visitId);
        $this->reviewRepositoryInterface->updateReview($localReviewEntitity['id'], $this->currentUserId, $data, $validated);
        $this->updateVisitInvestigatorFormStatus($validated);
    }

    private function updateVisitInvestigatorFormStatus(bool $validated) : void {
        if ($validated) {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DONE);
        }else {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
        }

    }

}

<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;

class InvestigatorFormService extends FormService {

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

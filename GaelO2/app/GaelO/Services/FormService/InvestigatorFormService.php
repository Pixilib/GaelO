<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Services\FormService\FormService;

class InvestigatorFormService extends FormService
{
    protected bool $local = true;

    public function saveForm(array $data, bool $validated, ?bool $adjudication = null): int
    {
        $this->abstractVisitRules->setFormData($data);
        if (!$this->abstractVisitRules->checkInvestigatorFormValidity($validated)) throw new GaelOBadRequestException('Form Constraints Failed');
        $localReviewId = $this->reviewRepositoryInterface->createReview(true, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, false);
        $this->updateVisitInvestigatorFormStatus($validated);
        return $localReviewId;
    }

    public function updateForm(int $reviewId, array $data, bool $validated)
    {
        $this->abstractVisitRules->setFormData($data);
        if (!$this->abstractVisitRules->checkInvestigatorFormValidity($validated)) throw new GaelOBadRequestException('Form Constraints Failed');
        $this->reviewRepositoryInterface->updateReview($reviewId, $this->currentUserId, $data, $validated);
        $this->updateVisitInvestigatorFormStatus($validated);
    }

    private function unlockQcIfNeeded()
    {
        if ($this->visitContext['state_quality_control'] !== Constants::QUALITY_CONTROL_NOT_NEEDED) $this->visitService->resetQc($this->visitId);
    }

    public function deleteForm(int $reviewId)
    {
        $this->reviewRepositoryInterface->delete($reviewId);
        //Make investigator form not done
        $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_NOT_DONE);
        $this->unlockQcIfNeeded();
    }

    public function unlockForm(int $reviewId)
    {
        $this->reviewRepositoryInterface->unlockReview($reviewId);
        //Make investigator form not done
        $this->updateVisitInvestigatorFormStatus(false);
        $this->unlockQcIfNeeded();
    }

    private function updateVisitInvestigatorFormStatus(bool $validated): void
    {
        if ($validated) {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DONE);
        } else {
            $this->visitService->updateInvestigatorFormStatus(Constants::INVESTIGATOR_FORM_DRAFT);
        }
    }
}

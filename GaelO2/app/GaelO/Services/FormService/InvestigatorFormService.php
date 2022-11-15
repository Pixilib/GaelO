<?php

namespace App\GaelO\Services\FormService;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\FormService\FormService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\VisitService;

class InvestigatorFormService extends FormService
{

    public function __construct(
        VisitService $visitService,
        MailServices $mailServices,
        FrameworkInterface $frameworkInterface,
        ReviewRepositoryInterface $reviewRepositoryInterface
    ) {
        parent::__construct($reviewRepositoryInterface, $visitService, $mailServices, $frameworkInterface);
        $this->local = true;
    }

    public function saveInvestigatorForm(array $data, bool $validated): int
    {
        $this->abstractVisitRules->setFormData($data);
        if (!$this->abstractVisitRules->checkInvestigatorFormValidity($validated)) throw new GaelOBadRequestException('Form Constraints Failed');
        $localReviewId = $this->reviewRepositoryInterface->createReview(true, $this->visitId, $this->studyName, $this->currentUserId, $data, $validated, false);
        $this->updateVisitInvestigatorFormStatus($validated);
        return $localReviewId;
    }

    public function updateInvestigatorForm(array $data, bool $validated): int
    {
        $this->abstractVisitRules->setFormData($data);
        if (!$this->abstractVisitRules->checkInvestigatorFormValidity($validated)) throw new GaelOBadRequestException('Form Constraints Failed');
        $localReviewEntitity = $this->reviewRepositoryInterface->getInvestigatorForm($this->visitId, false);
        $this->reviewRepositoryInterface->updateReview($localReviewEntitity['id'], $this->currentUserId, $data, $validated);
        $this->updateVisitInvestigatorFormStatus($validated);
        return $localReviewEntitity['id'];
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

<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitDecisions;

class TEST_FDG_PET0_DECISION extends AbstractVisitDecisions
{
    private $reviewConclusion = null;

    private ReviewRepositoryInterface $reviewRepositoryInterface;
    protected string $studyName = "TEST";

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function getReviewStatus(): string
    {
        //Fetch visit validated review
        $reviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($this->studyName, $this->visitContext['id'], true);
        if (sizeof($reviews) > 0) {
            $this->reviewConclusion = "Done";
            return ReviewStatusEnum::DONE->value;
        } else {
            return ReviewStatusEnum::NOT_DONE->value;
        }
    }

    public function getReviewConclusion(): ?string
    {
        return $this->reviewConclusion;
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }

    public function getAssociatedDataForInvestigatorForm(): array
    {
        return [
            'LastChemo' => '01/01/2021'
        ];
    }

    public function getAssociatedDataForReviewForm(): array
    {
        return [
            'Radiotherapy' => false
        ];
    }
}

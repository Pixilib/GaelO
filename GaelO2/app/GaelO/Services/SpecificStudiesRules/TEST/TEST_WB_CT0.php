<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;

class TEST_WB_CT0 extends AbstractVisitRules
{

    protected string $studyName = "TEST";
    private ReviewRepositoryInterface $reviewRepositoryInterface;

    public function __construct(ReviewRepositoryInterface $reviewRepositoryInterface)
    {
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
    }

    public function getInvestigatorValidationRules(): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public function getReviewerValidationRules(bool $adjudication): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public function getReviewStatus(): string
    {
        //Fetch visit validated review
        $reviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($this->studyName, $this->visitContext['id'], true);
        return sizeof($reviews) > 0 ? Constants::REVIEW_STATUS_DONE : Constants::REVIEW_STATUS_NOT_DONE;
    }

    public function getReviewConclusion(): ?string
    {
        return null;
    }

    public function getAllowedKeyAndMimeTypeInvestigator(): array
    {
        return [];
    }

    public function getAllowedKeyAndMimeTypeReviewer(): array
    {
        return [];
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }
}

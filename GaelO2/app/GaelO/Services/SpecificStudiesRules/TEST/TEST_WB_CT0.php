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

    public static function getInvestigatorValidationRules(): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public static function getReviewerValidationRules(): array
    {
        return [
            'comments' => [
                'rule' => self::RULE_STRING,
                'optional' => false
            ]
        ];
    }

    public static function getReviewerAdjudicationValidationRules(): array
    {
        return [];
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

    public static function getAllowedKeyAndMimeTypeInvestigator(): array
    {
        return [];
    }

    public static function getAllowedKeyAndMimeTypeReviewer(): array
    {
        return [];
    }

    public static function getAllowedKeyAndMimeTypeAdjudication(): array
    {
        return [];
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }
}

<?php

namespace App\GaelO\Services\SpecificStudiesRules\TEST;

use App\GaelO\Services\GaelOStudiesService\AbstractVisitRules;

class TEST_WB_CT0 extends AbstractVisitRules
{

    protected string $studyName = "TEST";

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

    public static function getAssociatedFilesVisit(): array
    {
        return [];
    }

    public static function getAssociatedFilesInvestigator(): array
    {
        return [];
    }

    public static function getAssociatedFilesReview(): array
    {
        return [];
    }

    public static function getAssociatedFilesAdjudication(): array
    {
        return [];
    }

    public static function getReviewerAdjudicationValidationRules(): array
    {
        return [];
    }

    public static function getVisitDecisionClass(): string
    {
        return TEST_FDG_PET0_DECISION::class;
    }
}

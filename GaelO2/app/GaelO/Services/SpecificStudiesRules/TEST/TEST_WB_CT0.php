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

    public static function getReviewerAdjudicationValidationRules(): array
    {
        return [];
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

    public static function getVisitDecisionClass(): string
    {
        return TEST_FDG_PET0_DECISION::class;
    }
}

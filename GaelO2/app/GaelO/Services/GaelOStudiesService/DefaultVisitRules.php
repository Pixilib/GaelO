<?php

namespace App\GaelO\Services\GaelOStudiesService;

class DefaultVisitRules extends AbstractVisitRules
{

    public static function getInvestigatorValidationRules(): array
    {
        return [];
    }

    public static function getReviewerValidationRules(): array    
    {
        return [];
    }

    public static function getReviewerAdjudicationValidationRules(): array
    {
        return [];
    }

    public static function getAllowedKeysAndMimeTypesInvestigator(): array
    {
        return [];
    }

    public static function getAllowedKeysAndMimeTypesReviewer(): array
    {
        return [];
    }

    public static function getAllowedKeysAndMimeTypesAdjudication(): array
    {
        return [];
    }

    public static function getVisitDecisionClass(): string
    {
        return DefaultVisitDecisions::class;
    }


}

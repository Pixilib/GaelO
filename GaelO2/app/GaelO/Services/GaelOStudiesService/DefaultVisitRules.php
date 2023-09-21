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

    public static function getVisitDecisionClass(): string
    {
        return DefaultVisitDecisions::class;
    }
}

<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Constants;
use Exception;

class DefaultVisitDecisions extends AbstractVisitDecisions
{
    public function getReviewStatus(): string
    {
        throw new Exception("Default Decision class should not be called");
    }

    public function getReviewConclusion(): ?string
    {
        throw new Exception("Default Decision class should not be called");
    }

    public function getTargetLesion(): ?array
    {
        throw new Exception("Default Decision class should not be called");
    }
}

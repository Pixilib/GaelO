<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Constants;

class DefaultVisitDecisions extends AbstractVisitDecisions
{
    public function getReviewStatus(): string
    {
        return Constants::REVIEW_STATUS_NOT_DONE;
    }

    public function getReviewConclusion(): ?string
    {
        return null;
    }

    public function getTargetLesion(): ?array
    {
        return null;
    }
}

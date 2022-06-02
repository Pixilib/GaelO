<?php

namespace App\GaelO\UseCases\GetReviewFormFromVisit;

class GetReviewFormFromVisitRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public ?int $userId = null;
}

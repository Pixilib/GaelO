<?php

namespace App\GaelO\UseCases\GetReviewsFromVisitType;

class GetReviewsFromVisitTypeRequest
{
    public string $studyName;
    public int $visitTypeId;
    public int $currentUserId;
}

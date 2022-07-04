<?php

namespace App\GaelO\UseCases\GetAssociatedDataForReview;

class GetAssociatedDataForReviewRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
}

<?php

namespace App\GaelO\UseCases\GetAssociatedFilesForReview;

class GetAssociatedFilesForReviewRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
}

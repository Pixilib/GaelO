<?php

namespace App\GaelO\UseCases\UnlockReviewForm;

class UnlockReviewFormRequest
{
    public int $currentUserId;
    public int $reviewId;
    public string $reason;
}

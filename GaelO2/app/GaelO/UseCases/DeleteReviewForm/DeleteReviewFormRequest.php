<?php

namespace App\GaelO\UseCases\DeleteReviewForm;

class DeleteReviewFormRequest
{
    public int $currentUserId;
    public int $reviewId;
    public string $reason;
}

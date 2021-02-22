<?php

namespace App\GaelO\UseCases\ModifyReviewForm;

class ModifyReviewFormRequest{
    public int $currentUserId;
    public int $reviewId;
    public array $data;
    public bool $validated;
}

<?php

namespace App\GaelO\UseCases\GetReviewProgression;

class GetReviewProgressionRequest{
    public string $studyName;
    public int $visitTypeId;
    public int $currentUserId;
}

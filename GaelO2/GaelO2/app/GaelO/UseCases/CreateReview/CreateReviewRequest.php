<?php

namespace App\GaelO\UseCases\CreateReview;

class CreateReviewRequest {

    public int $currentUserId;
    public string $studyName;
    public int $visitId;
    public string $role;
    public array $dataForm;

}

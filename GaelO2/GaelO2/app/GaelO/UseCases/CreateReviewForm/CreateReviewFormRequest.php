<?php

namespace App\GaelO\UseCases\CreateReviewForm;

class CreateReviewFormRequest {

    public int $currentUserId;
    public string $studyName;
    public int $visitId;
    public string $role;
    public array $data;
    public bool $validated;

}

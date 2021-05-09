<?php

namespace App\GaelO\UseCases\CreateFileToReview;

class CreateFileToReviewRequest{
    public int $currentUserId;
    public int $id;
    public string $binaryData;
    public string $contentType;
}

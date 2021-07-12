<?php

namespace App\GaelO\UseCases\GetReviewsMetadataFromVisitType;

class GetReviewsMetadataFromVisitTypeRequest
{
    public int $currentUserId;
    public int $visitTypeId;
    public string $studyName;
}

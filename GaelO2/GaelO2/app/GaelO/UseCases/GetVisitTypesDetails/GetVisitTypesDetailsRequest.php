<?php

namespace App\GaelO\UseCases\GetVisitTypesDetails;

class GetVisitTypesDetailsRequest {
    public int $currentUserId;
    public string $studyName;
    public array $visitTypesIds;
}

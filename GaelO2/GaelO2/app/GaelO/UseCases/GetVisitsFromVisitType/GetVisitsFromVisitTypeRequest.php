<?php

namespace App\GaelO\UseCases\GetVisitsFromVisitType;

class GetVisitsFromVisitTypeRequest {
    public string $studyName;
    public int $currentUserId;
    public int $visitTypeId;
}

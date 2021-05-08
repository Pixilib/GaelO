<?php

namespace App\GaelO\UseCases\GetDicomsStudiesFromVisitType;

class GetDicomsStudiesFromVisitTypeRequest {
    public int $currentUserId;
    public string $studyName;
    public int $visitTypeId;
}

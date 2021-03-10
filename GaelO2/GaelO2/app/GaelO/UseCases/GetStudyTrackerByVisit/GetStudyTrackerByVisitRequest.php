<?php

namespace App\GaelO\UseCases\GetStudyTrackerByVisit;

class GetStudyTrackerByVisitRequest {
    public int $currentUserId;
    public string $studyName;
    public int $visitId;
}

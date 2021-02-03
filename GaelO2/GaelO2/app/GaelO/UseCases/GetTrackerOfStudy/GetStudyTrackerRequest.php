<?php

namespace App\GaelO\UseCases\getStudyTracker;

class GetStudyTrackerRequest {
    public int $currentUserId;
    public string $studyName;
    public string $requiredTracker;
}

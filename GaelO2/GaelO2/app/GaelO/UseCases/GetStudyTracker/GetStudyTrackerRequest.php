<?php

namespace App\GaelO\UseCases\GetStudyTracker;

class GetStudyTrackerRequest {
    public int $currentUserId;
    public string $studyName;
    public string $role;
    public string $actionType;  //Role Investigator / Controller / Reviewer / Supervisor
}

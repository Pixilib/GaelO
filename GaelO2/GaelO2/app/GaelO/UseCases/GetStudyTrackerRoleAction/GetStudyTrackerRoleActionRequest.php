<?php

namespace App\GaelO\UseCases\GetStudyTrackerRoleAction;

class GetStudyTrackerRoleActionRequest {
    public int $currentUserId;
    public string $studyName;
    public string $role;
    public string $actionType;
    public string $trackerOfRole;
}

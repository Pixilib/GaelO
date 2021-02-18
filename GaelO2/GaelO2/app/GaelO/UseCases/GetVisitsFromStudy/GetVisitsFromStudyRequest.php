<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

class GetVisitsFromStudyRequest {
    public string $studyName;
    public string $role;
    public int $currentUserId;
}

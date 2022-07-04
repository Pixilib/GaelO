<?php

namespace App\GaelO\UseCases\GetPatientFromStudy;

class GetPatientFromStudyRequest
{
    public int $currentUserId;
    public string $role;
    public string $studyName;
}

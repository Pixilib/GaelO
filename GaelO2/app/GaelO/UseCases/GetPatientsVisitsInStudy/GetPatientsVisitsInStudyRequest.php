<?php

namespace App\GaelO\UseCases\GetPatientsVisitsInStudy;

class GetPatientsVisitsInStudyRequest
{
    public int $currentUserId;
    public string $role;
    public string $studyName;
    public array $patientIds;
}

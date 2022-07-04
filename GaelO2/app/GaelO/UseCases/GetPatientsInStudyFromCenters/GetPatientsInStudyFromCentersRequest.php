<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenters;

class GetPatientsInStudyFromCentersRequest
{
    public int $currentUserId;
    public string $role;
    public string $studyName;
    public array $centerCodes;
}

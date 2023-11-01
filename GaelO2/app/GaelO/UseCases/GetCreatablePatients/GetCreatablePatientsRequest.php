<?php

namespace App\GaelO\UseCases\GetCreatablePatients;

class GetCreatablePatientsRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $role;
}

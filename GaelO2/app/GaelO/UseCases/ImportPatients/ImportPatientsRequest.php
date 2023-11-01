<?php

namespace App\GaelO\UseCases\ImportPatients;

class ImportPatientsRequest
{
    public int $currentUserId;
    public array $patients;
    public string $studyName;
    public string $role;
}

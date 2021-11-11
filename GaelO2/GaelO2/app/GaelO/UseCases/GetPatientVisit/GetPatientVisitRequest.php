<?php

namespace App\GaelO\UseCases\GetPatientVisit;

class GetPatientVisitRequest {
    public int $currentUserId;
    public string $studyName;
    public string $patientId;
    public string $role;
    public bool $withTrashed;
}

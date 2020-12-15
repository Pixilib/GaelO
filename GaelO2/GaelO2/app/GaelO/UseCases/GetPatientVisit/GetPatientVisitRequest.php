<?php

namespace App\GaelO\UseCases\GetPatientVisit;

class GetPatientVisitRequest {
    public int $currentUserId;
    public string $studyName;
    public int $patientCode;
    public string $role;
}

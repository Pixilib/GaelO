<?php

namespace App\GaelO\UseCases\GetPatientVisit;

class GetPatientVisitRequest {
    public int $currentUserId;
    public int $patientCode;
    public string $role;
}

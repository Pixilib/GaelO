<?php

namespace App\GaelO\UseCases\GetPatientVisit;

class GetPatientVisitRequest {
    public int $visitId;
    public int $patientCode;
    public string $studyName;
    public string $role;
}

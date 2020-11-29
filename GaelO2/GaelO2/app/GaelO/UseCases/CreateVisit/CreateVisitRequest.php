<?php

namespace App\GaelO\UseCases\CreateVisit;

class CreateVisitRequest {
    public string $role;
    public string $studyName;
    public int $currentUserId;
    public int $patientCode;
    public ?string $acquisitionDate = null;
    public int $visitTypeId;
    public string $statusDone;
    public ?string $reasonForNotDone = null;
}

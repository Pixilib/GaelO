<?php

namespace App\GaelO\UseCases\CreateVisit;

class CreateVisitRequest {
    public string $studyName;
    public int $currentUserId;
    public string $patientId;
    public ?string $visitDate = null;
    public int $visitTypeId;
    public string $statusDone;
    public ?string $reasonForNotDone = null;
}

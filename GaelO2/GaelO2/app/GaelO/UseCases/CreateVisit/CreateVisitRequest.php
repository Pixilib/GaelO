<?php

namespace App\GaelO\UseCases\CreateVisit;

class CreateVisitRequest {
    public int $currentUserId;
    public string $patientId;
    public int $visitTypeId;
    public ?string $visitDate = null;
    public string $statusDone;
    public ?string $reasonForNotDone = null;
    public string $role;
}

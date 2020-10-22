<?php

namespace App\GaelO\UseCases\CreateVisit;

class CreateVisitRequest {
    public int $creatorUserId;
    public int $patientCode;
    public ?string $acquisitionDate = null;
    public int $visitTypeId;
    public string $statusDone;
    public ?string $reasonForNotDone = null;
}

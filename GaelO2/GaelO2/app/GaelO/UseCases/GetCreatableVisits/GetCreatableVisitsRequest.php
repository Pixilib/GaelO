<?php

namespace App\GaelO\UseCases\GetCreatableVisits;

class GetCreatableVisitsRequest{
    public int $currentUserId;
    public string $studyName;
    public string $patientCode;
}

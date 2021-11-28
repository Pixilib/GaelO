<?php

namespace App\GaelO\UseCases\GetPatient;

class GetPatientRequest {
    public string $id ;
    public string $studyName;
    public int $currentUserId;
    public string $role;
}

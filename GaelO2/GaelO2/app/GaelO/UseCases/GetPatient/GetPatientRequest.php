<?php

namespace App\GaelO\UseCases\GetPatient;

class GetPatientRequest {
    public int $code ;
    public int $currentUserId;
    public string $role;
}

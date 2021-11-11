<?php

namespace App\GaelO\UseCases\GetPatient;

class GetPatientRequest {
    public int $id ;
    public int $currentUserId;
    public string $role;
}

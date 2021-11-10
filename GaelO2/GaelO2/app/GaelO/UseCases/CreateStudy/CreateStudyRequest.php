<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest {
    public int $currentUserId;
    public String $name;
    public int $code;
    public int $patientNumberLength;
}

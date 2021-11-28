<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest {
    public int $currentUserId;
    public String $name;
    public String $code;
    public int $patientCodeLength;
}

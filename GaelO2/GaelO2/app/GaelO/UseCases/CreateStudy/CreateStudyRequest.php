<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest {
    public int $currentUserId;
    public String $studyName;
    public ?int $patientCodePreffix=null;
}

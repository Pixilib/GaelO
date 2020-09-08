<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest {
    public int $currentUserId;
    public int $studyName;
    public int $patientCodePreffix;
}

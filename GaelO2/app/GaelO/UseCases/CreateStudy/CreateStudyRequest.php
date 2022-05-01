<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest {
    public int $currentUserId;
    public String $name;
    public String $code;
    public int $patientCodeLength;
    public String $contactEmail;
    public bool $monitorShowAll;
    public bool $controllerShowAll;
    public ?string $ancillaryOf = null;
}

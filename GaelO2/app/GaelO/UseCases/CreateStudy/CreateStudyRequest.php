<?php

namespace App\GaelO\UseCases\CreateStudy;

class CreateStudyRequest
{
    public int $currentUserId;
    public String $name;
    public String $code;
    public int $patientCodeLength;
    public String $contactEmail;
    public bool $monitorShowAll;
    public bool $controllerShowAll;
    public bool $documentationMandatory;
    public ?string $ancillaryOf = null;
    public bool $creatablePatientsInvestigator;
    public bool $investigatorOwnVisit;
}

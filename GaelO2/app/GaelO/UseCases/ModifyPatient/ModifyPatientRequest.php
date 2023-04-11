<?php

namespace App\GaelO\UseCases\ModifyPatient;

class ModifyPatientRequest
{
    public int $currentUserId;
    public string $patientId;
    public string $studyName;
    public string $reason;
    public ?string $firstname = null;
    public ?string $lastname = null;
    public ?string $gender = null;
    public ?int $birthDay = null;
    public ?int $birthMonth = null;
    public ?int $birthYear = null;
    public ?string $registrationDate = null;
    public ?string $investigatorName = null;
    public int $centerCode;
    public string $inclusionStatus;
    public ?string $withdrawReason = null;
    public ?string $withdrawDate = null;
    public ?array $metadata = null;
}

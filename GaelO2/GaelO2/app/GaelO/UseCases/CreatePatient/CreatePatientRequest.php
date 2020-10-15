<?php

namespace App\GaelO\UseCases\CreatePatient;

class CreatePatientRequest {
    public int $code;
    public ?string $firstname;
    public ?string $lastname;
    public ?string $gender;
    public ?int $birthDay;
    public ?int $birthMonth;
    public ?int $birthYear;
    public string $registrationDate;
    public ?string $investigatorName;
    public ?int $centerCode;
    public ?string $studyName;
    public bool $withdraw;
    public ?string $withdrawReason;
    public ?string $withdrawDate;
}

<?php

namespace App\GaelO\Entities;


class PatientEntity {
    public int $code;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $gender;
    public ?int $birthDay;
    public ?int $birthMonth;
    public ?int $birthYear;
    public string $registrationDate;
    public ?string $investigatorName;
    public ?int $centerCode;
    public ?string $centerName;
    public ?string $countryCode;
    public string $studyName;
    public string $inclusionStatus;
    public ?string $withdrawReason;
    public ?string $withdrawDate;

    public static function fillFromDBReponseArray(array $array) : PatientEntity{
        $patientEntity  = new PatientEntity();
        $patientEntity->code = $array['code'];
        $patientEntity->lastName = $array['lastname'];
        $patientEntity->firstName = $array['firstname'];
        $patientEntity->birthDay = $array['birth_day'];
        $patientEntity->birthMonth = $array['birth_month'];
        $patientEntity->birthYear = $array['birth_year'];
        $patientEntity->gender = $array['gender'];
        $patientEntity->registrationDate = $array['registration_date'];
        $patientEntity->investigatorName = $array['investigator_name'];
        $patientEntity->studyName = $array['study_name'];
        $patientEntity->centerCode = $array['center_code'];
        $patientEntity->inclusionStatus = $array['inclusion_status'];
        $patientEntity->withdrawReason = $array['withdraw_reason'];
        $patientEntity->withdrawDate = $array['withdraw_date'];
        return $patientEntity;
    }

    public function fillCenterDetails(string $centerName, string $countryCode) : void {
        $this->centerName = $centerName;
        $this->countryCode = $countryCode;
    }

}

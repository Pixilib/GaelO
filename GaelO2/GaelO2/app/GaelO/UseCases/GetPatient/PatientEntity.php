<?php

namespace App\GaelO\UseCases\GetPatient;

class PatientEntity {
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

    public static function fillFromDBReponseArray(array $array){
        $patientEntity  = new PatientEntity();
        $patientEntity->code = $array['code'];
        $patientEntity->lastname = $array['last_name'];
        $patientEntity->firstname = $array['first_name'];
        $patientEntity->birthDay = $array['birth_day'];
        $patientEntity->birthMonth = $array['birth_month'];
        $patientEntity->birthYear = $array['birth_year'];
        $patientEntity->registrationDate = $array['registration_date'];
        $patientEntity->investigatorName = $array['investigator_name'];
        $patientEntity->studyName = $array['study_name'];
        $patientEntity->centerCode = $array['center_code'];
        $patientEntity->withdraw = $array['withdraw'];
        $patientEntity->withdrawReason = $array['withdraw_reason'];
        return $patientEntity;
    }

}

<?php

namespace App\GaelO\Entities;


class PatientEntity
{
    public string $id;
    public string $code;
    public ?string $firstname;
    public ?string $lastname;
    public ?string $gender;
    public ?int $birthDay;
    public ?int $birthMonth;
    public ?int $birthYear;
    public ?string $registrationDate;
    public ?string $investigatorName;
    public ?int $centerCode;
    public string $studyName;
    public string $inclusionStatus;
    public ?string $withdrawReason;
    public ?string $withdrawDate;
    public array $metadata;
    public CenterEntity $center;
    public array $visits = [];

    public static function fillFromDBReponseArray(array $array): PatientEntity
    {
        $patientEntity  = new PatientEntity();
        $patientEntity->id = $array['id'];
        $patientEntity->code = $array['code'];
        $patientEntity->lastname = $array['lastname'];
        $patientEntity->firstname = $array['firstname'];
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
        $patientEntity->metadata = $array['metadata'];
        return $patientEntity;
    }

    public static function fillMinimalFromDBReponseArray(array $array): PatientEntity
    {
        $patientEntity  = new PatientEntity();
        $patientEntity->id = $array['id'];
        $patientEntity->code = $array['code'];
        $patientEntity->registrationDate = $array['registration_date'];
        $patientEntity->studyName = $array['study_name'];
        $patientEntity->inclusionStatus = $array['inclusion_status'];
        $patientEntity->withdrawReason = $array['withdraw_reason'];
        $patientEntity->withdrawDate = $array['withdraw_date'];
        return $patientEntity;
    }

    public function fillCenterDetails(CenterEntity $centerEntity): void
    {
        $this->center = $centerEntity;
    }

    /**
     * visitEntities is an array of VisitEntities
     */
    public function setVisitsDetails(array $visitEntities): void
    {
        $this->visits = $visitEntities;
    }
}

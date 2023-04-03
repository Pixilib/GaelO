<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\Models\Patient;

class PatientRepository implements PatientRepositoryInterface
{

    private Patient $patientModel;

    public function __construct(Patient $patient)
    {
        $this->patientModel = $patient;
    }

    public function find($id): array
    {
        return $this->patientModel->findOrFail($id)->toArray();
    }

    public function getAllPatientsCodesInStudy(string $studyName): array
    {
        return $this->patientModel->where('study_name', $studyName)->select('code')->get()->pluck('code')->toArray();
    }

    public function getPatientWithCenterDetails(string $code): array
    {
        return $this->patientModel->with('center')->findOrFail($code)->toArray();
    }

    public function getPatientsInStudy(string $studyName, bool $withCenters): array
    {
        $patientQuery = $this->patientModel->where('study_name', $studyName);
        if($withCenters){
            $patientQuery->with('center');
        }
        $patients = $patientQuery->get();
        return empty($patients) ? [] : $patients->toArray();
    }

    public function getPatientsInStudyInCenters(string $studyName, array $centerCodes, bool $withCenters): array
    {
        $query = $this->patientModel->where('study_name', $studyName)->whereIn('center_code', $centerCodes);
        if($withCenters){
            $query->with('center');
        }
        $patients = $query->get();
        return empty($patients) ? [] : $patients->toArray();
    }

    public function getPatientsFromIdArray(array $ids, bool $withCenters): array
    {
        $query = $this->patientModel->whereIn('id', $ids);

        if($withCenters){
            $query->with('center');
        }

        $patients = $query->get();
        return $patients !== null  ? $patients->toArray() : [];
    }

    public function addPatientInStudy(
        string $id,
        string $code,
        ?string $lastname,
        ?string $firstname,
        ?string $gender,
        ?int $birthDay,
        ?int $birthMonth,
        ?int $birthYear,
        ?string $registrationDate,
        ?string $investigatorName,
        int $centerCode,
        string $inclusionStatus,
        string $studyName,
        ?array $metadata = null
    ): void {

        $patient = new Patient();
        $patient->id = $id;
        $patient->code = $code;
        $patient->lastname = $lastname;
        $patient->firstname = $firstname;
        $patient->gender = $gender;
        $patient->birth_day = $birthDay;
        $patient->birth_month = $birthMonth;
        $patient->birth_year = $birthYear;
        $patient->study_name = $studyName;
        $patient->registration_date = $registrationDate;
        $patient->investigator_name = $investigatorName;
        $patient->center_code = $centerCode;
        $patient->inclusion_status = $inclusionStatus;
        if($metadata) {
            $patient->metadata = $metadata;
        }
        $patient->save();
    }

    public function updatePatient(
        string $id,
        ?string $lastname,
        ?string $firstname,
        ?string $gender,
        ?int $birthDay,
        ?int $birthMonth,
        ?int $birthYear,
        string $studyName,
        ?string $registrationDate,
        ?string $investigatorName,
        int $centerCode,
        string $inclusionStatus,
        ?string $withdrawReason,
        ?string $withdrawDate,
        array $metadata
    ): void {

        $patient = $this->patientModel->findOrFail($id);

        $patient->lastname = $lastname;
        $patient->firstname = $firstname;
        $patient->gender = $gender;
        $patient->birth_day = $birthDay;
        $patient->birth_month = $birthMonth;
        $patient->birth_year = $birthYear;
        $patient->study_name = $studyName;
        $patient->registration_date = $registrationDate;
        $patient->investigator_name = $investigatorName;
        $patient->center_code = $centerCode;
        $patient->inclusion_status = $inclusionStatus;
        $patient->withdraw_reason = $withdrawReason;
        $patient->withdraw_date = $withdrawDate;
        $patient->metadata = $metadata;

        $patient->save();
    }

    public function updateInclusionStatus(string $id, string $inclusionStatus): void {
        $patient = $this->patientModel->findOrFail($id);
        $patient->inclusion_status = $inclusionStatus;
        $patient->save();
    }
}

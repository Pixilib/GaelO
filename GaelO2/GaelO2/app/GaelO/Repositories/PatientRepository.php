<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\Models\Patient;

class PatientRepository implements PatientRepositoryInterface
{

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    public function find($id): array
    {
        return $this->patient->findOrFail($id)->toArray();
    }

    public function getAllPatientsNumberInStudy(string $studyName): array
    {
        return $this->patient->where('study_name', $studyName)->select('code')->get()->pluck('code')->toArray();
    }

    public function getPatientWithCenterDetails(string $code): array
    {
        return $this->patient->with('center')->findOrFail($code)->toArray();
    }

    public function getPatientsInStudy(string $studyName): array
    {
        $patients = $this->patient->where('study_name', $studyName)->get();
        return empty($patients) ? [] : $patients->toArray();
    }

    public function getPatientsInStudyInCenters(string $studyName, array $centerCodes): array
    {
        $patients = $this->patient->where('study_name', $studyName)->whereIn('center_code', $centerCodes)->get();
        return empty($patients) ? [] : $patients->toArray();
    }

    public function getPatientsFromCodeArray(array $codes): array
    {
        $patients = $this->patient->whereIn('code', $codes)->get();
        return $patients !== null  ? $patients->toArray() : [];
    }

    public function getPatientsFromIdArray(array $ids): array
    {
        $patients = $this->patient->whereIn('id', $ids)->get();
        return $patients !== null  ? $patients->toArray() : [];
    }

    /**
     * @param $patients expected array of Patient Entity
     */
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
        String $studyName
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
        $patient->save();
    }

    public function updatePatient(
        int $id,
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
        ?string $withdrawDate
    ): void {

        $patient = $this->patient->findOrFail($id);

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

        $patient->save();
    }
}

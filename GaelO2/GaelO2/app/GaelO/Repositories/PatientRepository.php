<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\Models\Patient;
use App\GaelO\Entities\PatientEntity;
use Illuminate\Support\Facades\Log;

class PatientRepository implements PatientRepositoryInterface {

    public function __construct(Patient $patient){
        $this->patient = $patient;
    }

    //SK APPELEE A L EXTERIEUR A ENELEVER
    public function update($code, array $data) : void {
        $patient = $this->patient->findOrFail($code);
        foreach($patient->getAttributes() as $property => $value){
            $patient->$property = $data[$property];
        }
        $patient->save();
    }

    public function find($code) : array {
        return $this->patient->findOrFail($code)->toArray();
    }

    public function getAllPatientsCode() : array {
        return $this->patient->select('code')->get()->pluck('code')->toArray();
    }

    public function getPatientWithCenterDetails(int $code) : array {
        return $this->patient->with('center')->findOrFail($code)->toArray();
    }

    public function getPatientsInStudy(string $studyName) : array {
        $patients = $this->patient->where('study_name', $studyName)->get();
        return empty($patients) ? [] : $patients->toArray();
    }

    public function getPatientsInStudyInCenters(string $studyName, array $centersCode) : array {

        $patients = $this->patient->where('study_name', $studyName)->whereIn('center_code', $centersCode)->get();
        return empty($patients) ? [] : $patients->toArray();

    }

    /**
     * @param $patients expected array of Patient Entity
     */
    public function addPatientInStudy(PatientEntity $patientEntity, String $studyName) : void {

        $patient = new Patient();
        $patient->code = $patientEntity->code;
        $patient->lastname = $patientEntity->lastname;
        $patient->firstname = $patientEntity->firstname;
        $patient->gender = $patientEntity->gender;
        $patient->birth_day = $patientEntity->birthDay;
        $patient->birth_month = $patientEntity->birthMonth;
        $patient->birth_year = $patientEntity->birthYear;
        $patient->study_name = $studyName;
        $patient->registration_date = $patientEntity->registrationDate;
        $patient->investigator_name = $patientEntity->investigatorName;
        $patient->center_code = $patientEntity->centerCode;
        $patient->save();
    }

    public function updatePatient(int $code, string $lastname, string $firstname,
                            string $gender, int $birthDay, int $birthMonth, int $birthYear,
                            string $studyName, string $registrationDate, string $investigatorName, int $centerCode) : void {

        $patient = $this->patient->findOrFail($code);

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

}

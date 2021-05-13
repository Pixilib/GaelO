<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\PatientRepositoryInterface;
use App\Models\Patient;
use App\GaelO\UseCases\GetPatient\PatientEntity;
use App\GaelO\Util;

class PatientRepository implements PatientRepositoryInterface {

    public function __construct(Patient $patient){
        $this->patient = $patient;
    }

    private function create(array $data){
        $patient = new Patient();
        $model = Util::fillObject($data, $patient);
        $model->save();
    }

    public function update($code, array $data) : void {
        $model = $this->patient->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($code) : array {
        return $this->patient->findOrFail($code)->toArray();
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
        $arrayPatientEntity = [
            "code" => $patientEntity->code,
            "lastname" => $patientEntity->lastName,
            "firstname" => $patientEntity->firstName,
            "gender" => $patientEntity->gender,
            "birth_day" => $patientEntity->birthDay,
            "birth_month" => $patientEntity->birthMonth,
            "birth_year" => $patientEntity->birthYear,
            "study_name" => $studyName,
            "registration_date" => $patientEntity->registrationDate,
            "investigator_name" => $patientEntity->investigatorName,
            "center_code" => $patientEntity->centerCode
        ];
        $this->create($arrayPatientEntity);
    }

    public function updatePatient(int $code, string $lastname, string $firstname,
                            string $gender, int $birthDay, int $birthMonth, int $birthYear,
                            string $studyName, string $registrationDate, string $investigatorName, int $centerCode) : void {

        $arrayPatientEntity = array(
            "lastname" => $lastname,
            "firstname" => $firstname,
            "gender" => $gender,
            "birth_day" => $birthDay,
            "birth_month" => $birthMonth,
            "birth_year" => $birthYear,
            "study_name" => $studyName,
            "registration_date" => $registrationDate,
            "investigator_name" => $investigatorName,
            "center_code" => $centerCode
        );

        $this->update($code, $arrayPatientEntity);
    }

}

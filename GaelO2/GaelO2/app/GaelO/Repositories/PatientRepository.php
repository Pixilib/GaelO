<?php

namespace App\GaelO\Repositories;

use App\Patient;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\UseCases\GetPatient\PatientEntity;
use App\GaelO\Util;
use Illuminate\Support\Facades\Log;

class PatientRepository implements PersistenceInterface {

    public function __construct(Patient $patient){
        $this->patient = $patient;
    }

    public function create(array $data){
        $patient = new Patient();
        $model = Util::fillObject($data, $patient);
        $model->save();

    }

    public function update($code, array $data) : void {
        $model = $this->patient->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($code){
        return $this->patient->where('code', $code)->firstOrFail()->toArray();
    }

    public function delete($code) :void {
        $this->patient->find($code)->delete();
    }

    public function getAll() : array {
        $patients = $this->patient->get();
        return empty($patients) ? []  : $patients->toArray();
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

}

?>

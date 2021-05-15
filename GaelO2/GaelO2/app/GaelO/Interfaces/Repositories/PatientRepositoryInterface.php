<?php

namespace App\GaelO\Interfaces\Repositories;

use App\GaelO\Entities\PatientEntity;

interface PatientRepositoryInterface {

    public function update($code, array $data) : void ;

    public function find($code) : array ;

    public function getPatientWithCenterDetails(int $code) : array ;

    public function getPatientsInStudy(string $studyName) : array ;

    public function getPatientsInStudyInCenters(string $studyName, array $centersCode) : array ;

    public function addPatientInStudy(PatientEntity $patientEntity, String $studyName) : void ;

    public function updatePatient(int $code, string $lastname, string $firstname,
                    string $gender, int $birthDay, int $birthMonth, int $birthYear,
                    string $studyName, string $registrationDate, string $investigatorName, int $centerCode) : void ;

}


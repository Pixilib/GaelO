<?php

namespace App\GaelO\Interfaces\Repositories;

interface PatientRepositoryInterface {

    public function find($id) : array ;

    public function getAllPatientsNumberInStudy(string $studyName) : array ;

    public function getPatientWithCenterDetails(string $code) : array ;

    public function getPatientsInStudy(string $studyName) : array ;

    public function getPatientsInStudyInCenters(string $studyName, array $centerCodes) : array ;

    public function getPatientsFromCodeArray(array $codes) : array ;

    public function getPatientsFromIdArray(array $ids) : array ;

    public function addPatientInStudy(string $id,
                    string $code, string $lastname, string $firstname,
                    string $gender, int $birthDay, int $birthMonth, int $birthYear,
                    string $registrationDate, string $investigatorName, int $centerCode,
                    String $studyName) : void ;

    public function updatePatient(string $id, ?string $lastname, ?string $firstname,
                    ?string $gender, ?int $birthDay, ?int $birthMonth, ?int $birthYear,
                    string $studyName, ?string $registrationDate, ?string $investigatorName, int $centerCode,
                    string $inclusionStatus, ?string $withdrawReason, ?string $withdrawDate) : void ;

}


<?php

namespace App\GaelO\Interfaces\Repositories;

interface PatientRepositoryInterface
{

    public function find($id): array;

    public function getAllPatientsCodesInStudy(string $studyName): array;

    public function getPatientWithCenterDetails(string $code): array;

    public function getPatientsInStudy(string $studyName, bool $withCenters): array;

    public function getPatientsInStudyInCenters(string $studyName, array $centerCodes, bool $withCenters): array;

    public function getPatientsFromIdArray(array $ids, bool $withCenters): array;

    public function addPatientInStudy(
        string $id,
        string $code,
        string $lastname,
        string $firstname,
        string $gender,
        int $birthDay,
        int $birthMonth,
        int $birthYear,
        string $registrationDate,
        string $investigatorName,
        int $centerCode,
        string $inclusionStatus,
        string $studyName,
        ?array $metadata = null
    ): void;

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
    ): void;


    public function updateInclusionStatus(string $id, string $inclusionStatus): void;
}

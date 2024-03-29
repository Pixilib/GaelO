<?php

namespace App\GaelO\Interfaces\Repositories;

use App\GaelO\Entities\StudyEntity;

interface StudyRepositoryInterface
{

    public function find($name): StudyEntity;

    public function delete($name): void;

    public function addStudy(String $name, string $code, int $patientCodeLength, string $contactEmail, bool $controllerShowAll, bool $monitorShowAll, bool $documentationMandatory, ?string $ancillaryOf, bool $creatablePatientsInvestigator): void;

    public function isExistingStudyName(string $name): bool;

    public function getAncillariesStudyOfStudy(string $name): array;

    public function getStudies(bool $withTrashed = false): array;

    public function getStudyWithDetails(string $name): array;

    public function getAllStudiesWithDetails(): array;

    public function reactivateStudy(string $name): void;

    public function getStudyStatistics(string $name): array;

    public function isExistingStudyCode(string $code): bool;
}

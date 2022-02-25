<?php

namespace App\GaelO\Interfaces\Repositories;

interface StudyRepositoryInterface {

    public function find($name) : array ;

    public function delete($name) : void ;

    public function addStudy(String $name, string $code, int $patientCodeLength, string $contactEmail, ?string $ancillaryOf) : void ;

    public function isExistingStudy(string $name) : bool ;

    public function getAncillariesStudyOfStudy(string $name) : array ;

    public function getStudies(bool $withTrashed = false) : array ;

    public function getAllStudiesWithDetails() : array ;

    public function getStudyDetails(string $name) : array ;

    public function reactivateStudy(string $name) : void ;

    public function getStudyStatistics(string $name) : array;

}

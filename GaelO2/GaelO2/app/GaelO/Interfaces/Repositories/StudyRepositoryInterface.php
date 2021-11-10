<?php

namespace App\GaelO\Interfaces\Repositories;

interface StudyRepositoryInterface {

    public function find($name) : array ;

    public function delete($name) : void ;

    public function addStudy(String $name, int $code, int $patientNumberLength) : void ;

    public function isExistingStudy($name) : bool ;

    public function getStudies(bool $withTrashed = false) : array ;

    public function getAllStudiesWithDetails() : array ;

    public function getStudyDetails(string $name) : array ;

    public function reactivateStudy(string $name) : void ;

}

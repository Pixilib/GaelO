<?php

namespace App\GaelO\Interfaces;

interface StudyRepositoryInterface {

    public function addStudy(String $name, String $patientCodePrefix) : void ;

    public function isExistingStudy($name) : bool ;

    public function getStudies(bool $withTrashed = false) : array ;

    public function getAllStudiesWithDetails() : array ;

    public function getStudyDetails(string $name) : array ;

    public function reactivateStudy(string $name) : void ;

}

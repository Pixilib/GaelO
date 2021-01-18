<?php

namespace App\GaelO\Interfaces;

interface VisitGroupRepositoryInterface
{

    public function createVisitGroup(String $studyName, String $modality)  : void ;

    public function hasVisitTypes(int $visitGroupId) : bool ;

    public function isExistingVisitGroup(String $studyName, String $modality) : bool ;

}

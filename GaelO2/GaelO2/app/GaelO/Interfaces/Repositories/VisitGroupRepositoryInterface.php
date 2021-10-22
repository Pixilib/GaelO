<?php

namespace App\GaelO\Interfaces\Repositories;

interface VisitGroupRepositoryInterface
{

    public function find($id) ;

    public function delete($id) : void ;

    public function createVisitGroup(String $studyName, String $name, String $modality)  : void ;

    public function hasVisitTypes(int $visitGroupId) : bool ;

    public function isExistingVisitGroup(String $studyName, String $name) : bool ;

}

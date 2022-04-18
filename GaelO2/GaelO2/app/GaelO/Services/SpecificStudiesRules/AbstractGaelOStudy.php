<?php

namespace App\GaelO\Services\SpecificStudiesRules;

abstract class AbstractGaelOStudy {

    protected string $studyName;

    public abstract function getSpecificForm(string $visitGroupName, string $visitTypeName) : String ;

}

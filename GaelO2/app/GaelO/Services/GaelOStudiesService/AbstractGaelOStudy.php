<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;

abstract class AbstractGaelOStudy
{
    protected string $studyName;

    public abstract function getSpecificForm(string $visitGroupName, string $visitTypeName): String;

    public function getCreatableVisitCalculator() : DefaultCreatableVisitCalculator {
        return FrameworkAdapter::make(DefaultCreatableVisitCalculator::class);
    }

    /**
     * Facade to instanciate specific study object, if does not exist, return the DefaultGaelOStudy Object
     */
    public static function getSpecificStudyObject(string $studyName) : AbstractGaelOStudy {
        $class = '\App\GaelO\Services\SpecificStudiesRules\\'. $studyName .'\\' .  $studyName;
        if(class_exists($class)) return FrameworkAdapter::make($class);
        else return FrameworkAdapter::make(DefaultGaelOStudy::class);
    }

    public static function getSpecificStudiesRules(string $studyName, string $visitGroup, string $visitName): AbstractVisitRules
    {
        $studyObject = AbstractGaelOStudy::getSpecificStudyObject($studyName);
        $specificObjectClass = $studyObject->getSpecificForm($visitGroup, $visitName);
        return FrameworkAdapter::make($specificObjectClass);
    }
}

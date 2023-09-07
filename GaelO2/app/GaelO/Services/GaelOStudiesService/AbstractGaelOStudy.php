<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Services\GaelOStudiesService\Events\AbstractGaelOStudyEvent;

abstract class AbstractGaelOStudy
{
    protected string $studyName;

    public abstract function getVisitRulesClass(string $visitGroupName, string $visitTypeName): String;

    public function getSpecificVisitRules(string $visitGroup, string $visitName): AbstractVisitRules
    {
        $specificObjectClass = $this->getVisitRulesClass($visitGroup, $visitName);
        return FrameworkAdapter::make($specificObjectClass);
    }

    public function getCreatableVisitCalculator(): DefaultCreatableVisitCalculator
    {
        return FrameworkAdapter::make(DefaultCreatableVisitCalculator::class);
    }

    /**
     * For ancillaries studies, shall return the visitType ID array for which a review is expected
     */
    public function getReviewableVisitTypeIds(): null|array
    {
        return null;
    }

    /**
     * For ancillaries studies, shall return the patients metadata tags for which a review is expected
     */
    public function getReviewablePatientsTags(): null|array
    {
        return null;
    }

    /**
     * To make specific study action on study event
     */
    public function onEventStudy(AbstractGaelOStudyEvent $studyEvent): void
    {
        return;
    }

    /**
     * Facade to instanciate specific study object, if does not exist, return the DefaultGaelOStudy Object
     */
    public static function getSpecificStudyObject(string $studyName): AbstractGaelOStudy
    {
        $class = '\App\GaelO\Services\SpecificStudiesRules\\' . $studyName . '\\' .  $studyName;
        if (class_exists($class)) return FrameworkAdapter::make($class);
        else return FrameworkAdapter::make(DefaultGaelOStudy::class);
    }
}

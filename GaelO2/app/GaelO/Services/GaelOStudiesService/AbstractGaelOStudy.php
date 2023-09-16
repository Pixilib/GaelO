<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Interfaces\Adapters\JobInterface;
use App\GaelO\Services\GaelOStudiesService\Events\AbstractGaelOStudyEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitUploadedEvent;
use App\GaelO\Services\MailServices;

abstract class AbstractGaelOStudy
{
    protected MailServices $mailServices;
    protected JobInterface $jobInterface;
    protected string $studyName;

    public function __construct(MailServices $mailServices, JobInterface $jobInterface)
    {
        $this->mailServices = $mailServices;
        $this->jobInterface = $jobInterface;
    }

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
     * To make specific study action on study event, can be overriden to avoid some automatic mails
     */
    public function onEventStudy(AbstractGaelOStudyEvent $studyEvent): void
    {
        if ($studyEvent instanceof VisitUploadedEvent) {
            $studyName = $studyEvent->getStudyName();
            $patientId = $studyEvent->getPatientId();
            $patientCode = $studyEvent->getPatientCode();
            $visitId = $studyEvent->getVisitId();
            $qcNeeded = $studyEvent->isQcNeeded();
            $visitType = $studyEvent->getVisitTypeName();
            $creatorUserId = $studyEvent->getCreatorUserId();
            $reviewNeeded = $studyEvent->isReviewNeeded();

            $this->mailServices->sendUploadedVisitMessage($visitId, $creatorUserId, $studyName, $patientId, $patientCode, $visitType, $qcNeeded);

            if ($qcNeeded) {
                $this->jobInterface->sendQcReportJob($visitId);
            }

            if (!$qcNeeded && $reviewNeeded) {
                $this->mailServices->sendReviewReadyMessage($visitId, $studyName, $patientId, $patientCode, $visitType);
            }
        }
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

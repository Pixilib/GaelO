<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Interfaces\Adapters\JobInterface;
use App\GaelO\Services\GaelOStudiesService\Events\AwaitingAdjudicationEvent;
use App\GaelO\Services\GaelOStudiesService\Events\BaseStudyEvent;
use App\GaelO\Services\GaelOStudiesService\Events\CorrectiveActionEvent;
use App\GaelO\Services\GaelOStudiesService\Events\QCModifiedEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitConcludedEvent;
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
    public function onEventStudy(BaseStudyEvent $studyEvent): void
    {
        if ($studyEvent instanceof VisitUploadedEvent) {
            $this->onVisitUploaded($studyEvent);
        } else if ($studyEvent instanceof QCModifiedEvent) {
            $this->onQcModified($studyEvent);
        } else if ($studyEvent instanceof CorrectiveActionEvent) {
            $this->onCorrectiveAction($studyEvent);
        } else if ($studyEvent instanceof AwaitingAdjudicationEvent) {
            $this->onAwaitingAdjudication($studyEvent);
        } else if ($studyEvent instanceof VisitConcludedEvent) {
            $this->onVisitConcluded($studyEvent);
        }
    }

    protected function onVisitUploaded(VisitUploadedEvent $visitUploadedEvent): void
    {
        $studyName = $visitUploadedEvent->getStudyName();
        $patientId = $visitUploadedEvent->getPatientId();
        $patientCode = $visitUploadedEvent->getPatientCode();
        $visitId = $visitUploadedEvent->getVisitId();
        $qcNeeded = $visitUploadedEvent->isQcNeeded();
        $visitType = $visitUploadedEvent->getVisitTypeName();
        $creatorUserId = $visitUploadedEvent->getCreatorUserId();
        $reviewNeeded = $visitUploadedEvent->isReviewNeeded();

        $this->mailServices->sendUploadedVisitMessage($visitId, $creatorUserId, $studyName, $patientId, $patientCode, $visitType, $qcNeeded);

        if ($qcNeeded) {
            $this->jobInterface->sendQcReportJob($visitId);
        }

        if (!$qcNeeded && $reviewNeeded) {
            $this->mailServices->sendReviewReadyMessage($visitId, $studyName, $patientId, $patientCode, $visitType);
        }
    }

    protected function onQcModified(QCModifiedEvent $qcModifiedEvent)
    {
        $studyName = $qcModifiedEvent->getStudyName();
        $creatorId = $qcModifiedEvent->getCreatorUserId();
        $patientId = $qcModifiedEvent->getPatientId();
        $patientCode = $qcModifiedEvent->getPatientCode();
        $patientCenterCode = $qcModifiedEvent->getPatientCenterCode();
        $visitId = $qcModifiedEvent->getVisitId();
        $visitType = $qcModifiedEvent->getVisitTypeName();
        $currentUserId = $qcModifiedEvent->getCurrentUserId();
        $visitModality = $qcModifiedEvent->getVisitModality();
        $qcStatus = $qcModifiedEvent->getQcStatus();
        $this->mailServices->sendQcDecisionMessage(
            $visitId,
            $creatorId,
            $currentUserId,
            $studyName,
            $patientCenterCode,
            $qcStatus,
            $patientId,
            $patientCode,
            $visitModality,
            $visitType,
            $qcModifiedEvent->getFormQcStatus(),
            $qcModifiedEvent->getImageQcStatus(),
            $qcModifiedEvent->getFormQcComment(),
            $qcModifiedEvent->getImageQcComment()
        );
    }

    protected function onCorrectiveAction(CorrectiveActionEvent $correctiveActionEvent)
    {
        $studyName = $correctiveActionEvent->getStudyName();
        $patientId = $correctiveActionEvent->getPatientId();
        $patientCode = $correctiveActionEvent->getPatientCode();
        $visitId = $correctiveActionEvent->getVisitId();
        $visitType = $correctiveActionEvent->getVisitTypeName();
        $currentUserId = $correctiveActionEvent->getCurrentUserId();
        $visitModality = $correctiveActionEvent->getVisitModality();
        $correctiveActionDone = $correctiveActionEvent->getCorrectiveActionDone();
        $this->mailServices->sendCorrectiveActionMessage(
            $visitId,
            $currentUserId,
            $studyName,
            $correctiveActionDone,
            $patientId,
            $patientCode,
            $visitModality,
            $visitType
        );
    }

    protected function onAwaitingAdjudication(AwaitingAdjudicationEvent $event)
    {
        $studyName = $event->getStudyName();
        $patientId = $event->getPatientId();
        $patientCode = $event->getPatientCode();
        $visitId = $event->getVisitId();
        $visitType = $event->getVisitTypeName();
        $this->mailServices->sendAwaitingAdjudicationMessage($studyName, $patientId, $patientCode, $visitType, $visitId);
    }

    protected function onVisitConcluded(VisitConcludedEvent $event)
    {
        $studyName = $event->getStudyName();
        $patientId = $event->getPatientId();
        $patientCode = $event->getPatientCode();
        $visitId = $event->getVisitId();
        $visitType = $event->getVisitTypeName();
        $conclusion = $event->getConclusion();
        $uploaderUserId = $event->getUploaderUserId();
        $this->mailServices->sendVisitConcludedMessage(
            $visitId,
            $uploaderUserId,
            $studyName,
            $patientId,
            $patientCode,
            $visitType,
            $conclusion,
        );
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

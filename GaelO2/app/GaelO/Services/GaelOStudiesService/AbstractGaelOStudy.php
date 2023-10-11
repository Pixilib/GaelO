<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Interfaces\Adapters\JobInterface;
use App\GaelO\Interfaces\Repositories\ReviewRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Services\GaelOStudiesService\Events\AwaitingAdjudicationEvent;
use App\GaelO\Services\GaelOStudiesService\Events\BaseStudyEvent;
use App\GaelO\Services\GaelOStudiesService\Events\CorrectiveActionEvent;
use App\GaelO\Services\GaelOStudiesService\Events\QCModifiedEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitConcludedEvent;
use App\GaelO\Services\GaelOStudiesService\Events\VisitUploadedEvent;
use App\GaelO\Services\MailService\MailListBuilder;
use App\GaelO\Services\MailServices;

abstract class AbstractGaelOStudy
{
    protected MailServices $mailServices;
    protected UserRepositoryInterface $userRepositoryInterface;
    protected ReviewRepositoryInterface $reviewRepositoryInterface;
    protected JobInterface $jobInterface;
    protected string $studyName;

    public function __construct(MailServices $mailServices, UserRepositoryInterface $userRepositoryInterface, ReviewRepositoryInterface $reviewRepositoryInterface, JobInterface $jobInterface)
    {
        $this->mailServices = $mailServices;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->reviewRepositoryInterface = $reviewRepositoryInterface;
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
        $uploaderUserId = $visitUploadedEvent->getUploaderUserId();
        $reviewNeeded = $visitUploadedEvent->isReviewNeeded();

        //Send to supervisors and monitors of the study
        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR)
            ->withUserEmail($creatorUserId)
            ->withUserEmail($uploaderUserId);

        //If QC is awaiting add controllers
        if ($qcNeeded) {
            $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_CONTROLLER);
        }

        $this->mailServices->sendUploadedVisitMessage($mailListBuilder->get(), $visitId, $studyName, $patientId, $patientCode, $visitType);

        if ($qcNeeded) {
            $this->jobInterface->sendQcReportJob($visitId);
        }

        if (!$qcNeeded && $reviewNeeded) {
            $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
            $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_REVIEWER);
            $this->mailServices->sendReviewReadyMessage($mailListBuilder->get(), $visitId, $studyName, $patientId, $patientCode, $visitType);
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

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR)
            ->withUserEmail($creatorId)
            ->withUserEmail($currentUserId)
            ->withInvestigatorOfCenterInStudy($studyName, $patientCenterCode);

        $this->mailServices->sendQcDecisionMessage(
            $mailListBuilder->get(),
            $visitId,
            $studyName,
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

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_CONTROLLER)
            ->withUserEmail($currentUserId);

        $this->mailServices->sendCorrectiveActionMessage(
            $mailListBuilder->get(),
            $visitId,
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

        //Get All Users with Reviwers in this study
        $reviewersUsers = $this->userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_REVIEWER);

        //Get All Reviews of this visit
        $reviews = $this->reviewRepositoryInterface->getReviewsForStudyVisit($studyName, $visitId, true);
        $reviewerDoneUserIdArray = array_map(function ($user) {
            return $user['user_id'];
        }, $reviews);

        //Select users who didn't validate review form of this visit
        $availableReviewers = array_filter($reviewersUsers, function ($user) use ($reviewerDoneUserIdArray) {
            return !in_array($user['id'], $reviewerDoneUserIdArray);
        });

        //Build email list
        $availableReviewersEmails = array_map(function ($user) {
            return $user['email'];
        }, $availableReviewers);

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR);
        $supervisorEmails = $mailListBuilder->get();

        $emails = [
            ...$availableReviewersEmails,
            ...$supervisorEmails
        ];

        $this->mailServices->sendAwaitingAdjudicationMessage($emails, $studyName, $patientId, $patientCode, $visitType, $visitId);
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

        $mailListBuilder = new MailListBuilder($this->userRepositoryInterface);
        $mailListBuilder->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_SUPERVISOR)
            ->withUsersEmailsByRolesInStudy($studyName, Constants::ROLE_MONITOR);

        //If uplaoder need to be included
        if ($uploaderUserId) {
            $mailListBuilder->withUserEmail($uploaderUserId);
        }

        $this->mailServices->sendVisitConcludedMessage(
            $mailListBuilder->get(),
            $visitId,
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

<?php

namespace App\GaelO\Services\GaelOStudiesService;

use App\GaelO\Constants\Enums\ReviewStatusEnum;

abstract class AbstractVisitDecisions
{

    protected array $data = [];
    protected bool $isLocal;
    protected bool $adjudication;
    protected string $studyName;
    protected array $visitContext;

    public function setFormData(array $data)
    {
        $this->data = $data;
    }

    public function setLocalForm(bool $isLocal)
    {
        $this->isLocal = $isLocal;
    }

    public function setAdjudication(bool $adjudication)
    {
        $this->adjudication = $adjudication;
    }

    public function setVisitContext(array $visitContext)
    {
        $this->visitContext = $visitContext;
    }

    public function setStudyName(string $studyName)
    {
        $this->studyName = $studyName;
    }

    public function getPatientId()
    {
        return $this->visitContext['patient_id'];
    }

    public function getPatientCode()
    {
        return $this->visitContext['patient']['code'];
    }

    public function getVisitName(): string
    {
        return $this->visitContext['visit_type']['name'];
    }

    /**
     * Must return the review status for each action on review (send, delete, unlock), 
     * needs to handle backward and forward
     */
    abstract public function getReviewStatus(): string;

    /**
     * Return the conclusion value, must return null if review status is not done
     */
    abstract public function getReviewConclusion(): ?string;

    /**
     * Return custom data should be usefull to generate investigator form
     * Empty array by default, to be override in specific models
     */
    public function getAssociatedDataForInvestigatorForm(): array
    {
        return [];
    }

    /**
     * Return custom data should be usefull to generate review form
     * Empty array by default, to be override in specific models
     */
    public function getAssociatedDataForReviewForm(): array
    {
        return [];
    }

    abstract public function getTargetLesion(): ?array;

    public function getReviewAvailability(string $reviewStatus): bool
    {
        if ($reviewStatus === ReviewStatusEnum::DONE->value) {
            //If Done reached make the review unavailable for review
            return false;
        } else {
            //Needed in case of deletion of a review (even if true by default initialy, need to come back if deletion)
            return true;
        }
    }
}

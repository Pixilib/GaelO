<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class QCModifiedEvent extends BaseVisitEvent
{
    private int $userId;
    private string $qcStatus;
    private string $formQcStatus;
    private string $imageQcStatus;
    private string $formQcComment;
    private string $imageQcComment;
    private bool $reviewNeeded;

    public function __construct(array $visitEntity)
    {
        parent::__construct(GaelOStudyEventEnum::QC_MODIFIED_EVENT, $visitEntity);
    }

    public function setCurrentUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getCurrentUserId(): int
    {
        return $this->userId;
    }

    public function setQcStatus(string $qcStatus): void
    {
        $this->qcStatus = $qcStatus;
    }

    public function getQcStatus(): string
    {
        return $this->qcStatus;
    }

    public function setFormQcStatus(string $status): void
    {
        $this->formQcStatus = $status;
    }

    public function getFormQcStatus(): string
    {
        return $this->formQcStatus;
    }

    public function setImageQcStatus(string $status): void
    {
        $this->imageQcStatus = $status;
    }

    public function getImageQcStatus(): string
    {
        return $this->imageQcStatus;
    }

    public function setFormQcComment(string $formQcComment): void
    {
        $this->formQcComment = $formQcComment;
    }

    public function getFormQcComment(): string
    {
        return $this->formQcComment;
    }

    public function setImageQcComment(string $imageQcComment): void
    {
        $this->imageQcComment = $imageQcComment;
    }

    public function getImageQcComment(): string
    {
        return $this->imageQcComment;
    }

    public function setReviewNeeded(bool $needed): void
    {
        $this->reviewNeeded = $needed;
    }

    public function isReviewNeeded(): bool
    {
        return $this->reviewNeeded;
    }
}

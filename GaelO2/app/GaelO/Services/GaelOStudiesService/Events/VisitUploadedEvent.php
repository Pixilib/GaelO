<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

use App\GaelO\Constants\Enums\QualityControlStateEnum;

class VisitUploadedEvent extends AbstractGaelOStudyEvent
{
    private array $visitEntity;
    private bool $reviewNeeded;

    public function __construct(array $visitEntity)
    {
        $this->type = GaelOStudyEventEnum::UPLOAD_VISIT_EVENT;
        $this->visitEntity = $visitEntity;
    }

    public function getStudyName(): string
    {
        return $this->visitEntity['patient']['study_name'];
    }

    public function getVisitId(): int
    {
        return $this->visitEntity['id'];
    }

    public function getPatientId(): string
    {
        return $this->visitEntity['patient_id'];
    }

    public function getPatientCode(): string
    {
        return $this->visitEntity['patient']['code'];
    }

    public function setReviewNeeded(bool $reviewNeeded): void
    {
        $this->reviewNeeded = $reviewNeeded;
    }

    public function getVisitTypeName(): string
    {
        return $this->visitEntity['visit_type']['name'];
    }

    public function isQcNeeded(): bool
    {
        return $this->visitEntity['state_quality_control'] !== QualityControlStateEnum::NOT_NEEDED->value;
    }

    public function isReviewNeeded(): bool
    {
        return $this->reviewNeeded;
    }

    public function getCreatorUserId(): int
    {
        return $this->visitEntity['creator_user_id'];
    }
}

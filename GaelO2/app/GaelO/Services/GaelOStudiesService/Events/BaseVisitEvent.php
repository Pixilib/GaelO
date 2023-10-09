<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

use App\GaelO\Constants\Enums\QualityControlStateEnum;

class BaseVisitEvent extends BaseStudyEvent
{
    protected array $visitEntity;

    public function __construct(GaelOStudyEventEnum $type, array $visitEntity)
    {
        parent::__construct($type);
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

    public function getPatientCenterCode(): string
    {
        return $this->visitEntity['patient']['center_code'];
    }

    public function getVisitTypeName(): string
    {
        return $this->visitEntity['visit_type']['name'];
    }

    public function isQcNeeded(): bool
    {
        return $this->visitEntity['state_quality_control'] !== QualityControlStateEnum::NOT_NEEDED->value;
    }

    public function getCreatorUserId(): int
    {
        return $this->visitEntity['creator_user_id'];
    }

    public function getVisitModality(): string
    {
        return $this->visitEntity['visit_type']['visit_group']['modality'];
    }
}

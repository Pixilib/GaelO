<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class VisitUploadedEvent extends BaseVisitEvent
{
    private bool $reviewNeeded;

    public function __construct(array $visitEntity)
    {  
        parent::__construct(GaelOStudyEventEnum::UPLOAD_VISIT_EVENT, $visitEntity);
    }

    public function setReviewNeeded(bool $reviewNeeded): void
    {
        $this->reviewNeeded = $reviewNeeded;
    }

    public function isReviewNeeded(): bool
    {
        return $this->reviewNeeded;
    }

}

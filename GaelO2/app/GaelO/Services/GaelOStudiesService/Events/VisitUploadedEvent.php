<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class VisitUploadedEvent extends BaseVisitEvent
{
    private bool $reviewNeeded;
    private int $uploaderUserId;

    public function __construct(array $visitEntity, string $studyName)
    {  
        parent::__construct(GaelOStudyEventEnum::UPLOAD_VISIT_EVENT, $visitEntity, $studyName);
    }

    public function setUploaderUserId(int $userId): void
    {
        $this->uploaderUserId = $userId;
    }

    public function setReviewNeeded(bool $reviewNeeded): void
    {
        $this->reviewNeeded = $reviewNeeded;
    }

    public function isReviewNeeded(): bool
    {
        return $this->reviewNeeded;
    }

    public function getUploaderUserId() :int
    {
        return $this->uploaderUserId;
    }

}

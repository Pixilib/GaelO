<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class VisitConcludedEvent extends BaseVisitEvent
{
    private ?int $uploaderUserId;
    private ?string $conclusion;

    public function __construct(array $visitEntity)
    {
        parent::__construct(GaelOStudyEventEnum::VISIT_CONCLUDED_EVENT, $visitEntity);
    }

    public function setUploaderUserId(?int $userId): void
    {
        $this->uploaderUserId = $userId;
    }

    public function getUploaderUserId(): ?int
    {
        return $this->uploaderUserId;
    }

    public function setConclusion(?string $conclusion): void
    {
        $this->conclusion = $conclusion;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }
}

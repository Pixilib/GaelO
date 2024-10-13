<?php

namespace App\GaelO\Services\GaelOStudiesService\Events;

class CorrectiveActionEvent extends BaseVisitEvent
{
    private int $userId;
    private bool $correctiveActionDone;

    public function __construct(array $visitEntity, string $studyName)
    {
        parent::__construct(GaelOStudyEventEnum::CORRECTIVE_ACTION_EVENT, $visitEntity, $studyName);
    }

    public function setCurrentUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getCurrentUserId(): int
    {
        return $this->userId;
    }

    public function setCorrrectiveActionDone(bool $done): void
    {
        $this->correctiveActionDone = $done;
    }

    public function getCorrectiveActionDone(): bool
    {
        return $this->correctiveActionDone;
    }
}

<?php

namespace App\GaelO\UseCases\ModifyCorrectiveAction;

class ModifyCorrectiveActionRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public bool $newSeriesUploaded;
    public bool $newInvestigatorForm;
    public bool $correctiveActionDone;
    public ?string $comment = null;
}

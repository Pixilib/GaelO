<?php

namespace App\GaelO\UseCases\UnlockInvestigatorForm;

class UnlockInvestigatorFormRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $reason;
}

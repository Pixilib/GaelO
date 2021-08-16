<?php

namespace App\GaelO\UseCases\DeleteInvestigatorForm;

class DeleteInvestigatorFormRequest{
    public int $currentUserId;
    public int $visitId;
    public string $reason;
}

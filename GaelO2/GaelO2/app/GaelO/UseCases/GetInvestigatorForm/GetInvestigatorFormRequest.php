<?php

namespace App\GaelO\UseCases\GetInvestigatorForm;

class GetInvestigatorFormRequest{
    public int $currentUserId;
    public int $visitId;
    public string $role;
}

<?php

namespace App\GaelO\UseCases\GetInvestigatorForm;

class GetInvestigatorFormRequest
{
    public int $currentUserId;
    public string $studyName;
    public int $visitId;
    public string $role;
}

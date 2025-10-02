<?php

namespace App\GaelO\UseCases\GetAssociatedFilesForInvestigator;

class GetAssociatedFilesForInvestigatorRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $role;
}

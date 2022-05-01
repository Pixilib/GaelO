<?php

namespace App\GaelO\UseCases\GetAssociatedDataForInvestigator;

class GetAssociatedDataForInvestigatorRequest {
    public int $currentUserId;
    public int $visitId;
    public string $role;
}

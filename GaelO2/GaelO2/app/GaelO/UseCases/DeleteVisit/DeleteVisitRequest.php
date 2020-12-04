<?php

namespace App\GaelO\UseCases\DeleteVisit;

class DeleteVisitRequest {
    public int $currentUserId;
    public string $role;
    public int $visitId;
    public ?string $reason;
}

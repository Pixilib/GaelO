<?php

namespace App\GaelO\UseCases\DeleteVisit;

class DeleteVisitRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public string $role;
    public string $reason;
}

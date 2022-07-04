<?php

namespace App\GaelO\UseCases\RequestUnlock;

class RequestUnlockRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public string $role;
    public string $message;
}

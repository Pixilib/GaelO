<?php

namespace App\GaelO\UseCases\RequestUnlockQC;

class RequestUnlockQCRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $message;
}

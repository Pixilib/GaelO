<?php

namespace App\GaelO\UseCases\GetCenter;

class GetCenterRequest
{
    public ?int $code;
    public int $currentUserId;
    public ?string $studyName;
}

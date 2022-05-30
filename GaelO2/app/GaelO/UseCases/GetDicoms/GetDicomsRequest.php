<?php

namespace App\GaelO\UseCases\GetDicoms;

class GetDicomsRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $role;
    public int $visitId;
}

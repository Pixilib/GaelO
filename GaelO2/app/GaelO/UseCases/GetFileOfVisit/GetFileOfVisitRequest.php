<?php

namespace App\GaelO\UseCases\GetFileOfVisit;

class GetFileOfVisitRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $key;
    public string $role;
    public string $studyName;
}

<?php

namespace App\GaelO\UseCases\DeleteFileOfVisit;

class DeleteFileOfVisitRequest
{
    public int $currentUserId;
    public string $role;
    public string $studyName;
    public int $visitId;
    public string $key;
}

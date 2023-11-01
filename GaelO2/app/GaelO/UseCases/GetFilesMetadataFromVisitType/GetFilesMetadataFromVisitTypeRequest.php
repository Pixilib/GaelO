<?php

namespace App\GaelO\UseCases\GetFilesMetadataFromVisitType;

class GetFilesMetadataFromVisitTypeRequest
{
    public int $currentUserId;
    public int $visitTypeId;
    public string $studyName;
    public string $role;
}
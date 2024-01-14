<?php

namespace App\GaelO\UseCases\GetDicomStudyMetadata;

class GetDicomStudyMetadataRequest
{
    public string $studyInstanceUID;
    public int $currentUserId;
    public string $role;
}

<?php

namespace App\GaelO\UseCases\GetDicomSeriesMetadata;

class GetDicomSeriesMetadataRequest
{
    public string $seriesInstanceUID;
    public int $currentUserId;
    public string $role;
    public string $studyName;
}

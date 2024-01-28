<?php

namespace App\GaelO\UseCases\GetDicomSeriesPreview;

class GetDicomSeriesPreviewRequest
{
    public string $seriesInstanceUID;
    public int $index;
    public int $currentUserId;
    public string $role;
    public string $studyName;
}

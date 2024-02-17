<?php

namespace App\GaelO\UseCases\GetDicomSeriesTmtvReport;

class GetDicomSeriesTmtvReportRequest
{
    public string $seriesInstanceUID;
    public string $type;
    public string $methodology;
    public int $currentUserId;
    public string $role;
    public string $studyName;
}
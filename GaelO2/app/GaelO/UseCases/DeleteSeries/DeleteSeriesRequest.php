<?php

namespace App\GaelO\UseCases\DeleteSeries;

class DeleteSeriesRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $seriesInstanceUID;
    public string $role;
    public string $reason;
}

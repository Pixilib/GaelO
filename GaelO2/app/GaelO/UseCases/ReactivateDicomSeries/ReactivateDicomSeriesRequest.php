<?php

namespace App\GaelO\UseCases\ReactivateDicomSeries;

class ReactivateDicomSeriesRequest{
    public int $currentUserId;
    public string $seriesInstanceUID;
    public string $reason;
    public string $role;
}

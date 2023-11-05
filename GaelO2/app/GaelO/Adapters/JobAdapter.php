<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\JobInterface;
use App\Jobs\JobQcReport;
use App\Jobs\JobRadiomicsReport;

class JobAdapter implements JobInterface
{
    public function sendQcReportJob(int $visitId): void
    {
        JobQcReport::dispatch($visitId);
    }

    public function sendRadiomicsReport(int $visitId, ?int $behalfUserId): void
    {
        JobRadiomicsReport::dispatch($visitId, $behalfUserId);
    }
}

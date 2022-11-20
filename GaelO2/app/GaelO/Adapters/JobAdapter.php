<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\JobInterface;
use App\Jobs\JobQcReport;

class JobAdapter implements JobInterface
{
    public function sendQcReportJob(int $visitId): void
    {
        JobQcReport::dispatch($visitId);
    }
}

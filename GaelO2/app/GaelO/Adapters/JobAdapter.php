<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\JobInterface;
use App\Jobs\JobAutoQc;

class JobAdapter implements JobInterface {
    public function sendAutoQcJob(int $visitId) : void
    {
        JobAutoQc::dispatch($visitId);
    }
}

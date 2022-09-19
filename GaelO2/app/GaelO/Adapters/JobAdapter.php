<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\JobInterface;
use App\Jobs\JobAutoQc;

class JobAdatper implements JobInterface {
    public function sendAutoQcJob(int $visitId) : void
    {
        new JobAutoQc($visitId);
    }
}

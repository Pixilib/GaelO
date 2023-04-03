<?php

namespace App\GaelO\CronJobs;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

/**
 * Register custom GaelO scheduled taks in one place
 */
class GaelOScheduler
{

    public static function registerScheduledJobs(Schedule $schedule)
    {

        /*
        $schedule->call(function () {
            Log::info("Scheduled custom job");
        })->everyMinute();
        */
    }
}

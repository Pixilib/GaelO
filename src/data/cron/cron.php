<?php 

/**
 * This files is called from the crontab container every minute
 */

require_once(__DIR__.'/../../vendor/autoload.php');

echo('Cron started');

use GO\Scheduler;

// Create a new scheduler
$scheduler = new Scheduler();

$scheduler->php('import_patient.php')->sunday(23, 50);

// Let the scheduler execute jobs which are due.
$scheduler->run();
?>
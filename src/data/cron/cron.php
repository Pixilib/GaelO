<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * This files is called from the crontab container every minute
 * Add your cron action following 
 * https://github.com/peppeocchi/php-cron-scheduler
 */

require_once(__DIR__.'/../../vendor/autoload.php');

use GO\Scheduler;

// Create a new scheduler
$scheduler = new Scheduler();

//Define action and timing
$scheduler->php(__DIR__.'/import_patient.php')->monday(6, 00)->output('/var/log/gaelo_cron.log');
$scheduler->php(__DIR__.'/import_patient.php')->tuesday(6, 00)->output('/var/log/gaelo_cron.log');
$scheduler->php(__DIR__.'/import_patient.php')->wednesday(6, 00)->output('/var/log/gaelo_cron.log');
$scheduler->php(__DIR__.'/import_patient.php')->thursday(6, 00)->output('/var/log/gaelo_cron.log');
$scheduler->php(__DIR__.'/import_patient.php')->friday(6, 00)->output('/var/log/gaelo_cron.log');

// Let the scheduler execute jobs which are due.
$scheduler->run();

?>
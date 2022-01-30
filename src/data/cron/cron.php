<?php
/**
 Copyright (C) 2018-2020 KANOUN Salim
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
$scheduler=new Scheduler();

//Define action and timing


//scheduleEveryXMinutes("import_from_email_ennov.php", ["study" =>"TRAILOCLORI"] , 1);
// Let the scheduler execute jobs which are due.
$scheduler->run();


function testScheduleForDebug(String $scriptName, array $arugments, int $hour, int $min){
    global $scheduler;
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->everyMinute(15)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
}

function scheduleWorkingDays(String $scriptName, array $arugments, int $hour, int $min){
    global $scheduler;
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->monday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->tuesday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->wednesday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->thursday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->friday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
}

function scheduleEveryDays(String $scriptName, array $arugments, int $hour, int $min){
    global $scheduler;
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->daily($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
}

function scheduleSundays(String $scriptName, array $arugments, int $hour, int $min){
    global $scheduler;
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->sunday($hour, $min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
}

function scheduleEveryXMinutes(String $scriptName, array $arugments, int $min){
    global $scheduler;
    $scheduler->php(__DIR__.'/'.$scriptName, null, $arugments)->everyMinute($min)->output('/var/log/gaelo_cron_'.$scriptName.'.log');
}

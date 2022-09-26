<?php

namespace App\GaelO\Interfaces\Adapters;

interface JobInterface
{
    public function sendAutoQcJob(int $visitId) : void;
}
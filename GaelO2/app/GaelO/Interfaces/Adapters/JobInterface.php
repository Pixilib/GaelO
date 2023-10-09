<?php

namespace App\GaelO\Interfaces\Adapters;

interface JobInterface
{
    public function sendQcReportJob(int $visitId) : void;
    public function sendRadiomicsReport(int $visitId, string $behalfUserEmail) :void;
}
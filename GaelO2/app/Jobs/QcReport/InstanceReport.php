<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class InstanceReport
{
    private string $injectedDose;
    private string $injectedTime;
    private string $injectedDateTime;
    private string $injectedActivity;
    private string $radiopharmaceutical;
    private string $halfLife;

    public function fillData(OrthancMetaData $sharedTags)
    {
        $this->injectedDose = $sharedTags->getInjectedDose();
        $this->injectedTime = $sharedTags->getInjectedTime();
        $this->injectedDateTime = $sharedTags->getInjectedDateTime();
        $this->injectedActivity = $sharedTags->getInjectedActivity();
        $this->radiopharmaceutical = $sharedTags->getRadiopharmaceutical();
        $this->halfLife = $sharedTags->getHalfLife();
    }
}

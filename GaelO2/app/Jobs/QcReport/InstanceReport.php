<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class InstanceReport
{
    public string $injectedDose;
    public string $injectedTime;
    public string $injectedDateTime;
    public string $injectedActivity;
    public string $radiopharmaceutical;
    public string $halfLife;

    private string $orthancId;

    public function setOrthancId(string $orthancId){
        $this->orthancId = $orthancId;
    }

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

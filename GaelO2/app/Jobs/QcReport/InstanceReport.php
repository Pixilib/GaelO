<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class InstanceReport
{
    public $injectedDose;
    public $injectedTime;
    public $injectedDateTime;
    public $injectedActivity;
    public $radiopharmaceutical;
    public $halfLife;

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

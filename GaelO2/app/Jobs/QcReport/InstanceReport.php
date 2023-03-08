<?php

namespace App\Jobs\QcReport;

use App\GaelO\DicomUtils;
use App\GaelO\Services\StoreObjects\OrthancMetaData;

class InstanceReport
{
    public $injectedDose;
    public $injectedTime;
    public $injectedDateTime;
    public $specificActivity;
    public $radiopharmaceutical;
    public $halfLife;

    public function fillData(OrthancMetaData $sharedTags)
    {
        $this->injectedDose = $sharedTags->getInjectedDose();
        $this->injectedTime = $sharedTags->getInjectedTime();
        $this->injectedDateTime = $sharedTags->getInjectedDateTime();
        $this->specificActivity = $sharedTags->getSpecificActivity();
        $this->radiopharmaceutical = $sharedTags->getRadiopharmaceutical();
        $this->halfLife = $sharedTags->getHalfLife();
    }

    public function toArray()
    {
        return [
            'Injected Dose (Mbq)' => $this->injectedDose ?? null,
            'Injected Time' => $this->injectedTime ? DicomUtils::parseDicomTime($this->injectedTime, 'H:i:s') : null,
            'Injected DateTime' => $this->injectedDateTime ? DicomUtils::parseDicomDateTime($this->injectedDateTime, 'm/d/Y H:i:s') : null,
            'Specific Activity (Bq/micromole)' => $this->specificActivity ?? null,
            'Half Life (s)' => $this->halfLife  ?? null
        ];
    }
}

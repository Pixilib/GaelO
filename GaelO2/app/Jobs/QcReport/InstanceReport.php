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
    public $numberOfFrames;
    public $imageID;

    public function fillData(OrthancMetaData $dicomTags)
    {
        $this->injectedDose = $dicomTags->getInjectedDose();
        $this->injectedTime = $dicomTags->getInjectedTime();
        $this->injectedDateTime = $dicomTags->getInjectedDateTime();
        $this->specificActivity = $dicomTags->getSpecificActivity();
        $this->radiopharmaceutical = $dicomTags->getRadiopharmaceutical();
        $this->halfLife = $dicomTags->getHalfLife();
        $this->numberOfFrames = $dicomTags->getNumberOfFrames();
        $this->imageID = $dicomTags->getImageID();
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

<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class StudyReport
{

    private $studyDescription;
    private $manufacturer;
    private $studyDate;
    private $studyTime;

    private string $orthancId;

    public function setOrthancId(string $orthancId)
    {
        $this->orthancId = $orthancId;
    }

    public function fillData(OrthancMetaData $sharedTags)
    {
        $this->studyDescription = $sharedTags->getStudyDescription();
        $this->manufacturer = $sharedTags->getStudyManufacturer();
        $this->studyDate = $sharedTags->getStudyDate();
        $this->studyTime = $sharedTags->getStudyTime();
    }

    public function toArray()
    {

        return [
            'Study Description' => $this->studyDescription ?? null,
            'Manufacturer' => $this->manufacturer ?? null,
            'Study Date' => $this->studyDate ?? null,
        ];
    }
}

<?php
namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class StudyReport {

    private string $studyDescription;
    private string $manufacturer;
    private string $studyDate;
    private string $studyTime;

    public function fillData(OrthancMetaData $sharedTags){
        $this->studyDescription= $sharedTags->getStudyDescription();
        $this->manufacturer = $sharedTags->getStudyManufacturer();
        $this->studyDate = $sharedTags->getStudyDate();
        $this->studyTime = $sharedTags->getStudyTime();
    }


}
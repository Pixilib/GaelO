<?php

namespace App\GaelO\UseCases\GetDicoms;

class OrthancStudyEntity {
    public string $studyInstanceUID;
    public int $uploaderId;
    public string $uploadDate;
    public int $visitId;
    public bool $deleted;
    public ?string $acquisitionDate;
    public ?string $acquisitionTime;
    public ?string $studyDescription;
    public ?string $patientName;
    public ?string $patientId;
    public int $diskSize;
    public array $series = [];

    public static function fillFromDBReponseArray(array $array){
        $orthancStudy  = new OrthancStudyEntity();
        $orthancStudy->studyInstanceUID = $array['study_uid'];
        $orthancStudy->uploaderId = $array['uploader_id'];
        $orthancStudy->uploadDate = $array['upload_date'];
        $orthancStudy->visitId = $array['visit_id'];
        $orthancStudy->deleted = $array['deleted_at'] !== null;
        $orthancStudy->acquisitionDate = $array['acquisition_date'];
        $orthancStudy->acquisitionTime = $array['acquisition_time'];
        $orthancStudy->studyDescription = $array['study_description'];
        $orthancStudy->patientName = $array['patient_name'];
        $orthancStudy->patientId = $array['patient_id'];
        $orthancStudy->diskSize = $array['disk_size'];


        return $orthancStudy;
    }

}

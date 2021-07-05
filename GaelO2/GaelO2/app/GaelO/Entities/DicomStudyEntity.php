<?php

namespace App\GaelO\Entities;

class DicomStudyEntity
{
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
    public array $parentPatient;
    public array $parentVisit;
    public array $childSeries;
    public array $uploaderDetails;
    public array $series = [];

    public static function fillFromDBReponseArray(array $array)
    {
        $orthancStudy  = new DicomStudyEntity();
        $orthancStudy->studyInstanceUID = $array['study_uid'];
        $orthancStudy->uploaderId = $array['user_id'];
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

    public function addDicomSeries(array $dicomSeriesObjects): void
    {
        $this->childSeries = $dicomSeriesObjects;
    }

    public function addPatientDetails(array $patientData): void
    {
        $this->parentPatient = [
            'code' => $patientData['code'],
            'centerCode' => $patientData['center_code'],
            'inclusionStatus' => $patientData['inclusion_status'],
        ];
    }

    public function addVisitDetails(array $visitDetails): void
    {
        $this->parentVisit = [
            'modality' => $visitDetails['visit_type']['visit_group']['modality'],
            'visitTypeName' => $visitDetails['visit_type']['name'],
            'visitDate' => $visitDetails['visit_date'],
            'stateInvestigatorForm' => $visitDetails['state_investigator_form'],
            'stateQualityControl' => $visitDetails['state_quality_control']
        ];
    }

    public function addUploaderDetails(array $userDetails) : void
    {
        $this->uploaderDetails = [
            'username' => $userDetails['username']
        ];
    }
}

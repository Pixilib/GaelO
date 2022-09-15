<?php

namespace App\GaelO\Entities;

class DicomStudyEntity
{
    public string $studyInstanceUID;
    public int $uploaderId;
    public string $uploadDate;
    public int $visitId;
    public ?string $acquisitionDate;
    public ?string $acquisitionTime;
    public ?string $studyDescription;
    public ?string $patientName;
    public ?string $patientId;
    public int $diskSize;
    public bool $deleted;
    public array $series = [];
    public UserEntity $uploader;
    public PatientEntity $patient;
    public VisitEntity $visit;

    public static function fillFromDBReponseArray(array $array): DicomStudyEntity
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

    /**
     * dicomSeriesEntities is an array of DicomSeriesEntity
     */
    public function addDicomSeries(array $dicomSeriesEntities): void
    {
        $this->series = $dicomSeriesEntities;
    }

    public function addPatientDetails(array $patientData): void
    {
        $this->patient = new PatientEntity();
        $this->patient->id = $patientData['id'];
        $this->patient->code = $patientData['code'];
        $this->patient->centerCode = $patientData['center_code'];
        $this->patient->inclusionStatus = $patientData['inclusion_status'];
    }

    public function addVisitDetails(array $visitDetails): void
    {
        $this->visit = new VisitEntity();
        $this->visit->setVisitContext(
            $visitDetails['visit_type']['visit_group'],
            $visitDetails['visit_type']
        );
        $this->visit->visitDate = $visitDetails['visit_date'];
        $this->visit->stateInvestigatorForm = $visitDetails['state_investigator_form'];
        $this->visit->stateQualityControl = $visitDetails['state_quality_control'];
    }

    public function addUploaderDetails(UserEntity $userDetails): void
    {
        $this->uploader = $userDetails;
    }
}

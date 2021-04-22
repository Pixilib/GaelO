<?php

namespace App\GaelO\UseCases\GetDicomsStudy;

//SK CETTE CLASSE DOIT DISPARAITRE AU PROFIT D UN APPEL A DICOMSTUDYENTITY AVEC DETAILS
class GetDicomsStudyEntity {

    public string $studyInstanceUID;
    public string $studyDescription;
    public string $acquisitionDate;
    public string $acquisitionTime;
    public int $numberOfSeries;
    public int $numberOfInstances;
    public bool $deleted;
    public int $patientCode;
    public int $centerCode;
    public string $inclusionStatus;
    public string $modality;
    public string $visitTypeName;
    public string $visitDate;
    public string $stateInvestigatorForm;
    public string $stateQualityControl;
    public array $dicomSeries;
    public int $diskSize;
    public int $visitId;


    public static function fillFromDBReponseArray(array $array){
        $dicomStudyEntity  = new GetDicomsStudyEntity();
        $dicomStudyEntity->studyInstanceUID = $array['study_uid'];
        $dicomStudyEntity->numberOfSeries = $array['number_of_series'];
        $dicomStudyEntity->deleted = $array['deleted_at'] !== null;
        $dicomStudyEntity->acquisitionDate = $array['acquisition_date'];
        $dicomStudyEntity->acquisitionTime = $array['acquisition_time'];
        $dicomStudyEntity->studyDescription = $array['study_description'];
        $dicomStudyEntity->diskSize = $array['disk_size'];
        $dicomStudyEntity->patientCode = $array['code'];
        $dicomStudyEntity->centerCode = $array['center_code'];
        $dicomStudyEntity->inclusionStatus = $array['inclusion_status'];
        $dicomStudyEntity->modality = $array['modality'];
        $dicomStudyEntity->visitTypeName = $array['visitTypeName'];
        $dicomStudyEntity->visitDate = $array['visit_date'];
        $dicomStudyEntity->stateInvestigatorForm = $array['state_investigator_form'];
        $dicomStudyEntity->stateQualityControl = $array['state_quality_control'];
        $dicomStudyEntity->dicomSeries = $array['dicom_series'];

        return $dicomStudyEntity;
    }

}

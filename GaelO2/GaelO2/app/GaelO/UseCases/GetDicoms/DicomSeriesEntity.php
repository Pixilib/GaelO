<?php

namespace App\GaelO\UseCases\GetDicoms;

class DicomSeriesEntity {
    public string $seriesInstanceUID;
    public bool $deleted;
    public ?string $acquisitionDate;
    public ?string $acquisitionTime;
    public ?string $seriesDescription;
    public ?string $modality;
    public ?string $injectedDose;
    public ?string $radiopharmaceutical;
    public ?int $half_life;
    public ?string $injectedTime;
    public ?string $injectedDateTime;
    public ?int $injectedActivity;
    public ?int $patientWeight;
    public ?int $numberOfInstances;
    public ?string $seriesNumber;
    public ?string $manufacturer;
    public ?string $modelName;

    public int $diskSize;

    public static function fillFromDBReponseArray(array $array){
        $orthancSeries  = new DicomSeriesEntity();
        $orthancSeries->seriesInstanceUID = $array['series_uid'];
        $orthancSeries->deleted = $array['deleted_at'] !== null;
        $orthancSeries->acquisitionDate = $array['acquisition_date'];
        $orthancSeries->acquisitionTime = $array['acquisition_time'];
        $orthancSeries->seriesDescription = $array['series_description'];
        $orthancSeries->modality = $array['modality'];
        $orthancSeries->injectedDose = $array['injected_dose'];
        $orthancSeries->radiopharmaceutical = $array['radiopharmaceutical'];
        $orthancSeries->half_life = $array['half_life'];
        $orthancSeries->injectedTime = $array['injected_time'];
        $orthancSeries->injectedDateTime = $array['injected_datetime'];
        $orthancSeries->injectedActivity = $array['injected_activity'];
        $orthancSeries->patientWeight = $array['patient_weight'];
        $orthancSeries->numberOfInstances = $array['number_of_instances'];
        $orthancSeries->seriesNumber = $array['series_number'];
        $orthancSeries->manufacturer = $array['manufacturer'];
        $orthancSeries->modelName = $array['model_name'];
        $orthancSeries->diskSize = $array['disk_size'];


        return $orthancSeries;
    }

}

<?php

namespace App\GaelO\Entities;

class DicomSeriesEntity
{
    public string $seriesInstanceUID;
    public bool $deleted;
    public ?string $acquisitionDate;
    public ?string $acquisitionTime;
    public ?string $seriesDescription;
    public ?string $modality;
    public ?int $injectedDose;
    public ?string $radiopharmaceutical;
    public ?int $halfLife;
    public ?string $injectedTime;
    public ?string $injectedDateTime;
    public ?int $injectedActivity;
    public ?int $patientWeight;
    public ?int $numberOfInstances;
    public ?string $seriesNumber;
    public ?string $manufacturer;
    public ?string $modelName;

    public int $diskSize;

    public static function fillFromDBReponseArray(array $array): DicomSeriesEntity
    {
        $orthancSeries  = new DicomSeriesEntity();
        $orthancSeries->seriesInstanceUID = $array['series_uid'];
        $orthancSeries->deleted = $array['deleted_at'] !== null;
        $orthancSeries->acquisitionDate = $array['acquisition_date'];
        $orthancSeries->acquisitionTime = $array['acquisition_time'];
        $orthancSeries->seriesDescription = $array['series_description'];
        $orthancSeries->modality = $array['modality'];
        $orthancSeries->injectedDose = $array['injected_dose'];
        $orthancSeries->radiopharmaceutical = $array['radiopharmaceutical'];
        $orthancSeries->halfLife = $array['half_life'];
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

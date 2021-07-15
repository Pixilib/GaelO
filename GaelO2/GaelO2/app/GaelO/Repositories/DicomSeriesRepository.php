<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\Models\DicomSeries;

class DicomSeriesRepository implements DicomSeriesRepositoryInterface {


    private DicomSeries $dicomSeries;

    public function __construct(DicomSeries $dicomSeries){
        $this->dicomSeries = $dicomSeries;
    }

    public function deleteSeries(string $seriesInstanceUID) : void {
        $this->dicomSeries->where('series_uid',$seriesInstanceUID)->sole()->delete();
    }

    public function reactivateSeries(string $seriesInstanceUID) : void {
        $this->dicomSeries->withTrashed()->where('series_uid',$seriesInstanceUID)->sole()->restore();
    }

    public function addSeries(string $seriesOrthancID, string $studyInstanceUID, ?string $acquisitionDate,
                            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
                            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
                            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
                            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
                            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
                            ?string $modelName ) : void {

        $dicomSeries = new DicomSeries();
        $dicomSeries->orthanc_id = $seriesOrthancID;
        $dicomSeries->study_uid = $studyInstanceUID;
        $dicomSeries->acquisition_date = $acquisitionDate;
        $dicomSeries->acquisition_time = $acquisitionTime;
        $dicomSeries->modality = $modality;
        $dicomSeries->series_description  = $seriesDescription;
        $dicomSeries->injected_dose = $injectedDose;
        $dicomSeries->radiopharmaceutical = $radiopharmaceutical;
        $dicomSeries->half_life = $halfLife;
        $dicomSeries->injected_time = $injectedTime;
        $dicomSeries->injected_datetime = $injectedDateTime;
        $dicomSeries->injected_activity = $injectedActivity;
        $dicomSeries->patient_weight = $patientWeight;
        $dicomSeries->number_of_instances = $numberOfInstances;
        $dicomSeries->series_uid = $seriesUID;
        $dicomSeries->series_number = $seriesNumber;
        $dicomSeries->disk_size = $seriesDiskSize;
        $dicomSeries->uncompressed_disk_size = $seriesUncompressedDiskSize;
        $dicomSeries->manufacturer = $manufacturer;
        $dicomSeries->model_name = $modelName;

        $dicomSeries->save();

    }

    public function isExistingSeriesInstanceUID(string $seriesInstanceUID) : bool {
        return empty($this->dicomSeries->find($seriesInstanceUID)) ? false : true;
    }

    public function getSeries(string $seriesInstanceUID, bool $includeDeleted) : array {
        if($includeDeleted){
            $series = $this->dicomSeries->with('dicomStudy')->where('series_uid',$seriesInstanceUID)->withTrashed()->sole()->toArray();
        }else{
            $series = $this->dicomSeries->with('dicomStudy')->where('series_uid',$seriesInstanceUID)->sole()->toArray();
        }

        return $series;

    }

    public function getRelatedVisitIdFromSeriesInstanceUID(array $seriesInstanceUID) : array {
        $query = $this->dicomSeries
            ->join('dicom_studies', function ($join) {
                $join->on('dicom_series.study_uid', '=', 'dicom_studies.study_uid');
            })
            ->whereIn('series_uid', $seriesInstanceUID)
            ->select('visit_id')
            ->withTrashed();
        return $query->get()->pluck('visit_id')->unique()->toArray();
    }

    public function getSeriesOrthancIDOfSeriesInstanceUID(array $seriesInstanceUID) : array {
        $query = $this->dicomSeries
            ->whereIn('series_uid', $seriesInstanceUID)
            ->select('orthanc_id')
            ->withTrashed();
        return $query->get()->pluck('orthanc_id')->toArray();
    }

    public function getDicomSeriesOfStudyInstanceUIDArray(array $studyInstanceUID, bool $withTrashed) : array {

        $query = $this->dicomSeries->whereIn('study_uid', $studyInstanceUID);

        if($withTrashed){
            $query->withTrashed();
        }

        $answer = $query->get();

        return $answer->count() === 0 ? []  : $answer->toArray();
    }

}

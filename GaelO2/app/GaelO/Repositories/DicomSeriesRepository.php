<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\DicomSeriesRepositoryInterface;
use App\Models\DicomSeries;

class DicomSeriesRepository implements DicomSeriesRepositoryInterface
{
    private DicomSeries $dicomSeriesModel;

    public function __construct(DicomSeries $dicomSeries)
    {
        $this->dicomSeriesModel = $dicomSeries;
    }

    public function deleteSeries(string $seriesInstanceUID): void
    {
        $this->dicomSeriesModel->where('series_uid', $seriesInstanceUID)->sole()->delete();
    }

    public function reactivateSeries(string $seriesInstanceUID): void
    {
        $this->dicomSeriesModel->withTrashed()->where('series_uid', $seriesInstanceUID)->sole()->restore();
    }

    public function addSeries(
        string $seriesOrthancID,
        string $studyInstanceUID,
        ?string $acquisitionDate,
        ?string $acquisitionTime,
        ?string $modality,
        ?string $seriesDescription,
        ?int $injectedDose,
        ?string $radiopharmaceutical,
        ?int $halfLife,
        ?string $injectedTime,
        ?string $injectedDateTime,
        ?int $injectedActivity,
        ?int $patientWeight,
        int $numberOfInstances,
        string $seriesUID,
        ?string $seriesNumber,
        int $seriesDiskSize,
        int $seriesUncompressedDiskSize,
        ?string $manufacturer,
        ?string $modelName
    ): void {

        $dicomSeries = new DicomSeries();
        $dicomSeries->orthanc_id = $seriesOrthancID;
        $dicomSeries->study_instance_uid = $studyInstanceUID;
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

    public function getSeries(string $seriesInstanceUID, bool $withTrashed): array
    {
        $series = $this->dicomSeriesModel->with('dicomStudy')->where('series_uid', $seriesInstanceUID);

        if ($withTrashed) $series->withTrashed();
        return $series->sole()->toArray();
    }

    public function getRelatedVisitIdFromSeriesInstanceUID(array $seriesInstanceUID, bool $withTrashed): array
    {
        $query = $this->dicomSeriesModel
            ->with(['dicomStudy' => function ($query) use ($withTrashed) {
                if ($withTrashed) $query->withTrashed();
            }])
            ->whereIn('series_uid', $seriesInstanceUID);

        if ($withTrashed) $query->withTrashed();
        return $query->get()->pluck('dicomStudy.visit_id')->unique()->toArray();
    }

    public function getSeriesOrthancIDOfSeriesInstanceUID(array $seriesInstanceUID, bool $withTrashed): array
    {
        $query = $this->dicomSeriesModel
            ->whereIn('series_uid', $seriesInstanceUID)
            ->select('orthanc_id');

        if ($withTrashed) $query->withTrashed();
        return $query->get()->pluck('orthanc_id')->toArray();
    }

    public function getDicomSeriesOfStudyInstanceUIDArray(array $studyInstanceUID, bool $withTrashed): array
    {
        $query = $this->dicomSeriesModel->whereIn('study_instance_uid', $studyInstanceUID);

        if ($withTrashed) $query->withTrashed();

        $answer = $query->get();
        return $answer->count() === 0 ? []  : $answer->toArray();
    }
}

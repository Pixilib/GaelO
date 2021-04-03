<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\DicomSeriesRepositoryInterface;
use App\GaelO\Util;
use App\Models\DicomSeries;

class DicomSeriesRepository implements DicomSeriesRepositoryInterface {


    private DicomSeries $dicomSeries;

    public function __construct(DicomSeries $dicomSeries){
        $this->dicomSeries = $dicomSeries;
    }

    private function create(array $data) : void {
        $dicomSeries = new DicomSeries();
        $model = Util::fillObject($data, $dicomSeries);
        $model->save();
    }

    private function update($seriesInstanceUID, array $data) : void {
        $model = $this->dicomSeries->find($seriesInstanceUID);
        $model = Util::fillObject($data, $model);
        $model->save();
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

        $data = [
            'orthanc_id' => $seriesOrthancID,
            'study_uid' => $studyInstanceUID,
            'acquisition_date' => $acquisitionDate,
            'acquisition_time' => $acquisitionTime,
            'modality' => $modality,
            'series_description'=> $seriesDescription,
            'injected_dose'=>$injectedDose,
            'radiopharmaceutical'=>$radiopharmaceutical,
            'half_life'=>$halfLife,
            'injected_time'=>$injectedTime,
            'injected_datetime'=>$injectedDateTime,
            'injected_activity'=>$injectedActivity,
            'patient_weight'=>$patientWeight,
            'number_of_instances'=>$numberOfInstances,
            'series_uid'=>$seriesUID,
            'series_number'=>$seriesNumber,
            'disk_size'=>$seriesDiskSize,
            'uncompressed_disk_size'=>$seriesUncompressedDiskSize,
            'manufacturer'=>$manufacturer,
            'model_name'=>$modelName

        ];

        $this->create($data);

    }

    public function updateSeries(string $seriesOrthancID, ?string $acquisitionDate,
            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
            ?string $modelName ) : void {

        $data = [
        'acquisition_date' => $acquisitionDate,
        'acquisition_time' => $acquisitionTime,
        'modality' => $modality,
        'series_description'=> $seriesDescription,
        'injected_dose'=>$injectedDose,
        'radiopharmaceutical'=>$radiopharmaceutical,
        'half_life'=>$halfLife,
        'injected_time'=>$injectedTime,
        'injected_datetime'=>$injectedDateTime,
        'injected_activity'=>$injectedActivity,
        'patient_weight'=>$patientWeight,
        'number_of_instances'=>$numberOfInstances,
        'series_uid'=>$seriesUID,
        'series_number'=>$seriesNumber,
        'disk_size'=>$seriesDiskSize,
        'uncompressed_disk_size'=>$seriesUncompressedDiskSize,
        'manufacturer'=>$manufacturer,
        'model_name'=>$modelName

        ];

        $this->update($seriesUID, $data);

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

    public function reactivateSeriesOfStudyInstanceUID (string $studyInstanceUID) : void {
        $this->dicomSeries->where('study_uid',$studyInstanceUID)->withTrashed()->restore();
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

    //SK A TESTER
    public function getSeriesOrthancIDOfSeriesInstanceUID(array $seriesInstanceUID) : array {
        $query = $this->dicomSeries
            ->whereIn('series_uid', $seriesInstanceUID)
            ->select('orthanc_id')
            ->withTrashed();
        return $query->get()->pluck('orthanc_id')->toArray();
    }

}

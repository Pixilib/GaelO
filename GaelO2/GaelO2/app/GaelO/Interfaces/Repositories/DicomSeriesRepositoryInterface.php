<?php

namespace App\GaelO\Interfaces\Repositories;

interface DicomSeriesRepositoryInterface {

    public function addSeries(string $seriesOrthancID, string $studyInstanceUID, ?string $acquisitionDate,
                            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
                            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
                            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
                            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
                            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
                            ?string $modelName ) : void ;

    public function updateSeries(string $seriesOrthancID, ?string $acquisitionDate,
            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
            ?string $modelName ) :void ;

    public function deleteSeries(string $seriesInstanceUID) : void ;

    public function reactivateSeries(string $seriesInstanceUID) : void ;

    public function isExistingSeriesInstanceUID(string $seriesInstanceUID) : bool ;

    public function getSeries(string $seriesInstanceUID, bool $includeDeleted) : array ;

    public function getRelatedVisitIdFromSeriesInstanceUID(array $seriesInstanceUID) : array ;

    public function getSeriesOrthancIDOfSeriesInstanceUID(array $seriesInstanceUID) : array ;

    public function getDicomSeriesOfStudyInstanceUIDArray(array $studyInstanceUID, bool $withTrashed) : array ;

}

<?php

namespace App\GaelO\Interfaces;

interface OrthancSeriesRepositoryInterface {

    public function addSeries(string $seriesOrthancID, string $orthancStudyID, ?string $acquisitionDate,
                            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
                            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
                            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
                            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
                            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
                            ?string $modelName ) : void ;

    public function updateSeries(string $seriesOrthancID, string $orthancStudyID, ?string $acquisitionDate,
            ?string $acquisitionTime, ?string $modality, ?string $seriesDescription,
            ?int $injectedDose, ?string $radiopharmaceutical, ?int $halfLife,
            ?string $injectedTime,?string $injectedDateTime, ?int $injectedActivity, ?int $patientWeight,
            int $numberOfInstances, string $seriesUID, ?string $seriesNumber,
            int $seriesDiskSize, int $seriesUncompressedDiskSize, ?string $manufacturer,
            ?string $modelName ) :void ;

    public function deletebySeriesInstanceUID(string $seriesInstanceUID) : void ;

    public function reactivateBySeriesInstanceUID(string $seriesInstanceUID) : void ;

    public function isExistingOrthancSeriesID(string $orthancSeriesID) : bool ;

    public function getSeriesBySeriesInstanceUID(string $seriesInstanceUID, bool $includeDeleted) : array ;

    public function reactivateSeriesOfOrthancStudyID (string $orthancStudyID) : void ;

}

?>

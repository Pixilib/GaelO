<?php

namespace App\GaelO\Interfaces\Repositories;

interface DicomStudyRepositoryInterface {

    public function delete($studyInstanceUID) :void ;

    public function addStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                    ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                    string $studyUID, ?string $studyDescription, string $patientOrthancID,
                    ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                    int $diskSize, int $uncompressedDisksize  ) : void ;

    public function isExistingOriginalOrthancStudyID(string $orthancStudyID, string $studyName) : bool ;

    public function getStudyInstanceUidFromVisit(int $visitID) : string ;

    public function isExistingDicomStudyForVisit(int $visitID) : bool ;

    public function getDicomsDataFromVisit(int $visitID, bool $withDeletedStudy, bool $withDeletedSeries) : array ;

    public function getDicomStudy(string $studyInstanceUID, bool $includeDeleted) : array ;

    public function getChildSeries(string $studyInstanceUID, bool $onlyTrashed) : array ;

    public function reactivateByStudyInstanceUID(string $studyInstanceUID) :void ;

    public function getDicomStudyFromVisitIdArray(array $visitId, bool $withTrashed) : array ;

    public function getDicomStudyFromVisitIdArrayWithSeries(array $visitId, bool $withTrashedStudies, bool $withTrashedSeries) : array ;

}

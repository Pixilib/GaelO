<?php

namespace App\GaelO\Interfaces;

interface DicomStudyRepositoryInterface {

    public function delete($studyInstanceUID) :void ;

    public function addStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                    ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                    string $studyUID, ?string $studyDescription, string $patientOrthancID,
                    ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                    int $diskSize, int $uncompressedDisksize  ) : void ;

    public function updateStudy(string $orthancStudyID, int $visitID, int $uploaderID, string $uploadDate,
                                ?string $acquisitionDate, ?string $acquisitionTime, string $anonFromOrthancID,
                                string $studyUID, ?string $studyDescription, string $patientOrthancID,
                                ?string $patientName, ?string $patientID, int $numberOfSeries, int $numberOfInstance,
                                int $diskSize, int $uncompressedDisksize ) : void ;

    public function isExistingOriginalOrthancStudyID(string $orthancStudyID, string $studyName) : bool ;

    public function isExistingStudyInstantUID(string $orthancStudyID) : bool ;

    public function getStudyInstanceUidFromVisit(int $visitID) : string ;

    public function isExistingDicomStudyForVisit(int $visitID) : bool ;

    public function getDicomsDataFromVisit(int $visitID, bool $withDeleted) : array ;

    public function getOrthancStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted) : array ;

    public function getChildSeries(string $studyInstanceUID, bool $deleted) : array ;

    public function reactivateByStudyInstanceUID(string $studyInstanceUID) :void ;

}

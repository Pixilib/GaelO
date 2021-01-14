<?php

namespace App\GaelO\Interfaces;

interface OrthancStudyRepositoryInterface {



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

    public function isExistingOrthancStudyID(string $orthancStudyID) : bool ;

    public function getStudyOrthancIDFromVisit(int $visitID) : string ;

    public function isExistingDicomStudyForVisit(int $visitID) : bool ;

    public function getDicomsDataFromVisit(int $visitID, bool $withDeleted) : array ;

    public function getOrthancStudyByStudyInstanceUID(string $studyInstanceUID, bool $includeDeleted) : array ;

    public function getChildSeries(string $orthancStudyID, bool $deleted) : array ;

    public function reactivateByStudyInstanceUID(string $studyInstanceUID) :void ;

}

<?php

namespace App\GaelO\UseCases\ValidateWsiUpload;

class ValidateWsiUploadRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $originalOrthancId;
    public array $uploadedFileTusId;
    public int $numberOfInstances;
    public string $patientName;
    public string $studyDescription;
    public string $studyID;
    public string $accessionNumber;
    public string $manufacturer;
    public string $focusMathod;
    public string $extendedDepthOfField;
    public string $imageType;
    public array $specimenDescriptionSequence;
    public array $slides;

}

<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class StudyReport
{
    private string $seriesDescription;
    private string $modality;
    private string $seriesDate;
    private string $seriesTime;
    private string $acquisitionDate;
    private string $acquisitionTime;
    private string $sliceThickness;
    private string $pixelSpacing;
    private string $fov;
    private string $matrixSize;
    private string $patientPosition;
    private string $patientOrientation;
    private string $numberOfInstances;

    private string $scanningSequence;
    private string $sequenceVariant;
    private string $echoTime;
    private string $inversionTime;
    private string $echoTrainLength;
    private string $spacingBetweenSlices;
    private string $protocolName;

    private string $patientWeight;
    private string $patientHeight;
    private string $previewImagePath;

    public function setNumberOfInstances(int|string $numberOfInstances)
    {
        $this->numberOfInstances = $numberOfInstances;
    }

    public function setPreviewImagePath(string $path){
        $this->previewImagePath = $path;
    }

    public function fillData(OrthancMetaData $sharedTags)
    {
        $this->seriesDescription = $sharedTags->getSeriesDescription();
        $this->modality = $sharedTags->getSeriesModality();
        $this->seriesDate =  $sharedTags->getSeriesDate();
        $this->seriesTime = $sharedTags->getSeriesTime();
        $this->acquisitionDate = $sharedTags->getAcquisitonDate();
        $this->acquisitionTime = $sharedTags->getAcquisitonTime();
        $this->sliceThickness = $sharedTags->getSliceThickness();
        $this->pixelSpacing = $sharedTags->getPixelSpacing();
        $this->fov = $sharedTags->getFieldOfView();
        $this->matrixSize = $sharedTags->getMatrixSize();
        $this->patientPosition = $sharedTags->getPatientPosition();
        $this->patientOrientation = $sharedTags->getImageOrientation();

        if ($this->modality == 'MR') {
            $this->scanningSequence = $sharedTags->getScanningSequence();
            $this->sequenceVariant = $sharedTags->getSequenceVariant();
            $this->echoTime = $sharedTags->getEchoTime();
            $this->inversionTime = $sharedTags->getInversionTime();
            $this->echoTrainLength = $sharedTags->getEchoTrainLength();
            $this->spacingBetweenSlices = $sharedTags->getSpacingBetweenSlices();
            $this->protocolName = $sharedTags->getProtocolName();
        } else if ($this->modality == 'PT') {
            $this->patientWeight = $sharedTags->getPatientWeight();
            $this->patientHeight = $sharedTags->getPatientHeight();
        }
    }
}

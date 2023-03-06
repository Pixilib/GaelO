<?php

namespace App\Jobs\QcReport;

use App\GaelO\Services\StoreObjects\OrthancMetaData;

class SeriesReport
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

    private string $orthancId;
    private InstanceReport $instanceReport;
    private StudyReport $studyReport;

    public function setOrthancId(string $orthancId)
    {
        $this->orthancId = $orthancId;
    }

    public function setNumberOfInstances(int|string $numberOfInstances)
    {
        $this->numberOfInstances = $numberOfInstances;
    }

    public function getNumberOfInstances(): int
    {
        return $this->numberOfInstances;
    }

    public function setPreviewImagePath(string $path)
    {
        $this->previewImagePath = $path;
    }

    public function setInstanceReport(InstanceReport $instanceReport)
    {
        $this->instanceReport = $instanceReport;
    }

    public function fillData(OrthancMetaData $sharedTags)
    {


        $this->studyReport = new StudyReport();
        $this->studyReport->fillData($sharedTags);

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

    public function toArray()
    {

        return [
            'Series Description' => $this->seriesDescription ?? null,
            'Modality' => $this->modality ?? null,
            'Series Date' => $this->seriesDate ?? null,
            'Series Time' => $this->seriesTime ?? null,
            'Acquisition Date' => $this->acquisitionDate ?? null,
            'Acquisition Time' => $this->acquisitionTime ?? null,
            'Slice Thickness' => $this->sliceThickness ?? null,
            'Pixel Spacing' => $this->pixelSpacing ?? null,
            'FOV' => $this->fov ?? null,
            'Matrix Size' => $this->matrixSize ?? null,
            'Patient position' => $this->patientPosition ?? null,
            'Patient orientation' => $this->patientOrientation ?? null,
            'Number of instances' => $this->numberOfInstances ?? null,
            'Scanning sequence' => $this->scanningSequence ?? null,
            'Sequence variant' => $this->sequenceVariant ?? null,
            'Echo Time' => $this->echoTime ?? null,
            'Inversion Time' => $this->inversionTime ?? null,
            'Echo Train Length' => $this->echoTrainLength ?? null,
            'Spacing Between Slices' => $this->spacingBetweenSlices ?? null,
            'Protocol Name' => $this->protocolName ?? null,
            'Patient weight' => $this->patientWeight ?? null,
            'Patient height' => $this->patientHeight ?? null,
            'Injected Dose' => $this->instanceReport->injectedDose ?? null,
            'Injected Time' => $this->instanceReport->injectedTime ?? null,
            'Injected DateTime' => $this->instanceReport->injectedDateTime ?? null,
            'Injected Activity' => $this->instanceReport->injectedActivity ?? null,
            'Half Life' => $this->instanceReport->halfLife  ?? null
        ];
    }
}

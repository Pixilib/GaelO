<?php

namespace App\Jobs\QcReport;

use App\GaelO\DicomUtils;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\StoreObjects\OrthancMetaData;
use Throwable;

class SeriesReport
{
    private $SOPClassUID;
    private $seriesDescription;
    private $modality;
    private $seriesDate;
    private $seriesTime;
    private $acquisitionDate;
    private $acquisitionTime;
    private $sliceThickness;
    private $pixelSpacing;
    private $fov;
    private $matrixSize;
    private $patientPosition;
    private $patientOrientation;
    private $scanningSequence;
    private $sequenceVariant;
    private $echoTime;
    private $inversionTime;
    private $echoTrainLength;
    private $spacingBetweenSlices;
    private $protocolName;
    private $patientWeight;
    private $patientHeight;
    private array $previewImagePath = [];
    private array $orthancInstanceIds;
    private string $seriesOrthancId;
    private InstanceReport $instanceReport;
    private StudyReport $studyReport;

    public function __construct(string $seriesOrthancId)
    {
        $this->seriesOrthancId = $seriesOrthancId;
    }

    public function setInstancesOrthancIds(array $orthancInstancesIds)
    {
        $this->orthancInstanceIds = $orthancInstancesIds;
    }

    public function getNumberOfInstances(): int
    {
        return sizeof($this->orthancInstanceIds);
    }

    public function addPreviewImagePath(?string $path)
    {
        $this->previewImagePath[] = $path;
    }

    public function deletePreviewImages()
    {
        foreach ($this->previewImagePath as $imagePath) {
            if ($imagePath != null) unlink($imagePath);
        }
    }

    public function setInstanceReport(InstanceReport $instanceReport)
    {
        $this->instanceReport = $instanceReport;
    }

    public function fillData(OrthancMetaData $sharedTags)
    {
        $this->studyReport = new StudyReport();
        $this->studyReport->fillData($sharedTags);
        $this->SOPClassUID = $sharedTags->getSOPClassUID();
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

    public function getStudyDetails(): array
    {
        return $this->studyReport->toArray();
    }

    private function getPreviewType(): ImageType
    {
        if (!$this->instanceReport) {
            throw new GaelOException("Instance Report need to be set first");
        }

        $mosaicIDs = ['1.2.840.10008.5.1.4.1.1.4', '1.2.840.10008.5.1.4.1.1.4.1'];
        $gifIDs = [
            '1.2.840.10008.5.1.4.1.1.2', '1.2.840.10008.5.1.4.1.1.2.1', '1.2.840.10008.5.1.4.1.1.20',
            '1.2.840.10008.5.1.4.1.1.128', '1.2.840.10008.5.1.4.1.1.130', '1.2.840.10008.5.1.4.1.1.128.1'
        ];

        if ($this->instanceReport->numberOfFrames > 1) {
            //Multi frame image series
            return ImageType::MULTIFRAME;
        }

        if (sizeof($this->orthancInstanceIds) > 1) {
            if (in_array($this->SOPClassUID, $mosaicIDs)) {
                return ImageType::MOSAIC;
            } else if (in_array($this->SOPClassUID, $gifIDs)) {
                return ImageType::MIP;
            }
        }

        #All others case use default rendering of the first instance
        return ImageType::DEFAULT;
    }

    public function loadSeriesPreview(OrthancService $orthancService, GaelOProcessingService $gaelOProcessingService): void
    {
        $imageType = $this->getPreviewType();

        $imagePath = [];


        try {
            if ($imageType === ImageType::DEFAULT) {
                $imagePath[] = $orthancService->getInstancePreview($this->orthancInstanceIds[0]);
            } else {
                $isPet = $this->modality == 'PT';
                $payload = $isPet ? ['min' => 0, 'max' => 5] : [];
                $orthancService->sendDicomToProcessing($this->seriesOrthancId, $gaelOProcessingService);
                $processingSeriesId = $gaelOProcessingService->createSeriesFromOrthanc($this->seriesOrthancId, $isPet, $isPet);
                switch ($imageType) {
                    case ImageType::MIP:
                        //Mosaic for now as mip need significant computation and memory backend
                        $imagePath[] = $gaelOProcessingService->createMIPForSeries($processingSeriesId, $payload);
                        break;
                    case ImageType::MOSAIC:
                        $imagePath[] = $gaelOProcessingService->createMosaicForSeries($processingSeriesId, $payload);
                        break;
                    case ImageType::MULTIFRAME:
                        $imagePath = array_map(function ($instanceOrthancId) use ($gaelOProcessingService) {
                            return $gaelOProcessingService->createMosaicForSeries('instances', $instanceOrthancId);
                        }, $this->orthancInstanceIds);
                        break;
                }
            }
        } catch (Throwable $t) {
        }

        $this->previewImagePath = $imagePath;
    }

    public function toArray()
    {

        $instanceData = $this->instanceReport ? $this->instanceReport->toArray() : [];
        $numberOfInstances = $this->getNumberOfInstances();

        return [
            'Series Description' => $this->seriesDescription ?? null,
            'Modality' => $this->modality ?? null,
            'Series Date' => $this->seriesDate ? DicomUtils::parseDicomDate($this->seriesDate, 'm/d/Y') : null,
            'Series Time' => $this->seriesTime ? DicomUtils::parseDicomTime($this->seriesTime) : null,
            'Acquisition Date' => $this->acquisitionDate ? DicomUtils::parseDicomDate($this->acquisitionDate, 'm/d/Y') :  null,
            'Acquisition Time' => $this->acquisitionTime ? DicomUtils::parseDicomTime($this->acquisitionTime) : null,
            'Slice Thickness (mm)' => $this->sliceThickness ?? null,
            'Pixel Spacing (mm)' => $this->pixelSpacing ?? null,
            'FOV (mm)' => $this->fov ?? null,
            'Matrix Size' => $this->matrixSize ?? null,
            'Patient position' => $this->patientPosition ?? null,
            'Patient orientation' => $this->patientOrientation ?? null,
            'Number of instances' => $numberOfInstances,
            'Scanning sequence' => $this->scanningSequence ?? null,
            'Sequence variant' => $this->sequenceVariant ?? null,
            'Echo Time (ms)' => $this->echoTime ?? null,
            'Inversion Time (ms)' => $this->inversionTime ?? null,
            'Echo Train Length' => $this->echoTrainLength ?? null,
            'Spacing Between Slices (mm)' => $this->spacingBetweenSlices ?? null,
            'Protocol Name' => $this->protocolName ?? null,
            'Patient weight (kg)' => $this->patientWeight ?? null,
            'Patient height (m)' => $this->patientHeight ?? null,
            'image_path' => $this->previewImagePath,
            ...$instanceData
        ];
    }
}

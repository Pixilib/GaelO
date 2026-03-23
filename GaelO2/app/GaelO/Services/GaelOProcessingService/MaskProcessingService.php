<?php

namespace App\GaelO\Services\GaelOProcessingService;

use App\GaelO\Constants\Enums\ProcessingMaskEnum;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\OrthancService;

class MaskProcessingService
{

    private string $maskId;
    private string $seriesImageId;
    private string $dicomSeriesOrthancId;
    private GaelOProcessingService $gaelOProcessingService;
    private OrthancService $orthancService;

    public function __construct(
        OrthancService $orthancService,
        GaelOProcessingService $gaelOProcessingService,
    ) {
        $this->gaelOProcessingService = $gaelOProcessingService;
        $this->orthancService = $orthancService;
        $this->orthancService->setOrthancServer(true);
    }

    public function setMaskId(string $maskId)
    {
        $this->maskId = $maskId;
    }

    public function getMaskId(): string
    {
        return $this->maskId;
    }

    public function setSeriesId(string $seriesImageId, string $dicomSeriesOrthancId)
    {
        $this->seriesImageId = $seriesImageId;
        $this->dicomSeriesOrthancId = $dicomSeriesOrthancId;
    }

    public function getMaskAs(ProcessingMaskEnum $type, ?string $orientation = null, ?string $seriesDescription = null, ?string $modelName = null, ?string $ctAnatomyMaskId = null, ?string $ctRegionalMaskId = null) : string
    {
        if ($type === ProcessingMaskEnum::NIFTI) {
            $exportFile = $this->gaelOProcessingService->getMaskDicomOrientation($this->maskId, $orientation, true);
        } else if ($type === ProcessingMaskEnum::RTSS) {
            $rtssId = $this->gaelOProcessingService->createRtssFromMask($this->dicomSeriesOrthancId, $this->maskId, $seriesDescription, $modelName, $ctAnatomyMaskId, $ctRegionalMaskId);
            $exportFile = $this->gaelOProcessingService->getRtss($rtssId);
            //remove downloaded data from processing
            $this->gaelOProcessingService->deleteRessource("rtss", $rtssId);
        } else if ($type === ProcessingMaskEnum::SEG) {
            $segId = $this->gaelOProcessingService->createSegFromMask($this->dicomSeriesOrthancId, $this->maskId, $seriesDescription, $modelName, $ctAnatomyMaskId, $ctRegionalMaskId);
            $exportFile = $this->gaelOProcessingService->getSeg($segId);
            //remove downloaded data from processing
            $this->gaelOProcessingService->deleteRessource("seg", $segId);
        }

        return $exportFile;
    }

    public function getStatsOfMask(): array
    {
        return $this->gaelOProcessingService->getStatsMaskSeries($this->maskId, $this->seriesImageId);
    }

    public function fragmentMask(): MaskProcessingService
    {
        $fragmentedMaskId = $this->gaelOProcessingService->fragmentMask($this->seriesImageId, $this->maskId, true);
        $maskProcessingService = new MaskProcessingService($this->orthancService, $this->gaelOProcessingService);
        $maskProcessingService->setMaskId($fragmentedMaskId);
        $maskProcessingService->setSeriesId($this->seriesImageId, $this->dicomSeriesOrthancId);
        return $maskProcessingService;
    }

    public function thresholdMaskTo41(): MaskProcessingService
    {
        $threshold41MaskId = $this->gaelOProcessingService->thresholdMask($this->maskId, $this->seriesImageId, "41%");
        $maskProcessingService = new MaskProcessingService($this->orthancService, $this->gaelOProcessingService);
        $maskProcessingService->setMaskId($threshold41MaskId);
        $maskProcessingService->setSeriesId($this->seriesImageId, $this->dicomSeriesOrthancId);
        return $maskProcessingService;
    }

    public function createTepMaskMip(): string
    {
        $mipFragmentedPayload = ['maskId' => $this->maskId, 'delay' => 0.3, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $this->gaelOProcessingService->createMIPForSeries($this->seriesImageId, $mipFragmentedPayload);
        return $mipMask;
    }
}

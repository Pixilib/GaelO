<?php

namespace App\GaelO\Services;

use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;

class MaskProcessingService
{

    private string $maskId;
    private string $petId;
    private string $petSeriesOrthancId;
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

    public function setPetId(string $petId, string $petSeriesOrthancId)
    {
        $this->petId = $petId;
        $this->petSeriesOrthancId = $petSeriesOrthancId;
    }

    public function getMaskAs(string $type, string $orientation)
    {
        //TODO a passer en enum
        if ($type === 'nifti') {
            $exportFile = $this->gaelOProcessingService->getMaskDicomOrientation($this->maskId, $orientation, true);
        } else if ($type === "seg") {
            $rtssId = $this->gaelOProcessingService->createRtssFromMask($this->petSeriesOrthancId, $this->maskId);
            //$this->addCreatedRessource('rtss', $rtssId);
            $exportFile = $this->gaelOProcessingService->getRtss($rtssId);
            //A delete ici ?
            //$this->gaelOProcessingService->deleteRessource($gaeloProcessingFile->getType(), $gaeloProcessingFile->getId());
        } else if ($type === "rtss") {
            $segId = $this->gaelOProcessingService->createSegFromMask($this->petSeriesOrthancId, $this->maskId);
            //$this->addCreatedRessource('seg', $segId);
            $exportFile = $this->gaelOProcessingService->getSeg($segId);
            //A delete ici ?
            //$this->gaelOProcessingService->deleteRessource($gaeloProcessingFile->getType(), $gaeloProcessingFile->getId());
        }

        return $exportFile;
    }

    public function getStatsOfMask()
    {
        $stats = $this->gaelOProcessingService->getStatsMaskSeries($this->maskId, $this->petId);
        $statValue = [
            'TMTV 41%' => $stats['volume'],
            'Dmax (voxel)' => $stats['dmax'],
            'SUVmax' => $stats['suvmax'],
            'SUVmean' => $stats['suvmean'],
            'SUVpeak' => $stats['suvpeak'],
            'TLG' => $stats['tlg'],
            'Dmax Bulk' => $stats['dmaxbulk'],
        ];
    }

    public function thresholdMaskTo41(): MaskProcessingService
    {
        $threshold41MaskId = $this->gaelOProcessingService->thresholdMask($this->maskId, $this->petId, "41%");
        $maskProcessingService = new MaskProcessingService($this->orthancService, $this->gaelOProcessingService);
        $maskProcessingService->setMaskId($threshold41MaskId);
        $maskProcessingService->setPetId($this->petId, $this->petSeriesOrthancId);
        return $maskProcessingService;
    }

    public function createTepMaskMip(): string
    {
        $mipFragmentedPayload = ['maskId' => $this->maskId, 'delay' => 0.3, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $this->gaelOProcessingService->createMIPForSeries($this->petId, $mipFragmentedPayload);
        return $mipMask;
    }
}

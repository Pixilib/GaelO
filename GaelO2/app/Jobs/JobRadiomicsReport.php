<?php

namespace App\Jobs;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use App\GaelO\Services\MailServices;
use App\GaelO\Services\OrthancService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class JobRadiomicsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 300;
    public $tries = 1;
    private int $visitId;

    public function __construct(int $visitId)
    {
        $this->visitId = $visitId;
    }

    public function handle(OrthancService $orthancService, GaelOProcessingService $gaelOProcessingService, FrameworkInterface $frameworkInterface, MailServices $mailServices): void
    {
        $orthancSeriesIdPt = '40f008c4-18e01723-3bf8793d-5e1d2cfb-af1b3802';
        $orthancSeriesIdCt = '8460a711-e055e4b2-1747def1-0db79fdf-f33d2944';

        $idPT = $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdPt, true, true);
        $idCT = $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdCt);

        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');
        $orthancResponse = $orthancService->getZipStreamToFile([$orthancSeriesIdPt, $orthancSeriesIdCt], fopen($downloadedFilePath, 'r'));
        $gaelOProcessingService->createDicom($$downloadedFilePath);
        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $gaelOProcessingService->executeInference('attentionunet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];

        #Do Mask Fragmentation and threshold
        $fragmentedMaskId = $gaelOProcessingService->fragmentMask($idPT, $maskId);
        $threshold41MaskId = $gaelOProcessingService->thresholdMask($fragmentedMaskId, $idPT, "41%");

        #Fragmented Mip
        $mipFragmentedPayload = ['maskId' => $threshold41MaskId, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);
        $frameworkInterface->storeFile('fragmentedInferenceTest.gif', fopen($mipMask, 'r'));

        #get Rtss
        $rtssId = $gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $threshold41MaskId);
        $rtssFile = $gaelOProcessingService->getRtss($rtssId);
        $frameworkInterface->storeFile('rtss_41.dcm', fopen($rtssFile, 'r'));

        #get Seg
        $segId = $gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $threshold41MaskId);
        $segFile = $gaelOProcessingService->getSeg($segId);
        $frameworkInterface->storeFile('seg_41.dcm', fopen($segFile, 'r'));

        #get Nifti Dicom
        $maskdicom = $gaelOProcessingService->getMaskDicomOrientation($threshold41MaskId, 'LPI', false);
        $frameworkInterface->storeFile('mask_41_dicom.nii', fopen($maskdicom, 'r'));

        #get Stats
        $stats = $gaelOProcessingService->getStatsMask($threshold41MaskId);
        $mailServices->sendRadiomicsReport("TEST", $mipMask, [
            'tmtv' => $stats['volume'],
            'dmax' => $stats['dMax']
        ]);

        $gaelOProcessingService->deleteRessource('dicoms', $orthancSeriesIdPt);
        $gaelOProcessingService->deleteRessource('dicoms', $orthancSeriesIdCt);
        $gaelOProcessingService->deleteRessource('series', $idPT);
        $gaelOProcessingService->deleteRessource('series', $idCT);
        $gaelOProcessingService->deleteRessource('masks', $maskId);
        $gaelOProcessingService->deleteRessource('masks', $fragmentedMaskId);
        $gaelOProcessingService->deleteRessource('masks', $threshold41MaskId);
        $gaelOProcessingService->deleteRessource('rtss', $rtssId);
        $gaelOProcessingService->deleteRessource('seg', $segId);
    }

}
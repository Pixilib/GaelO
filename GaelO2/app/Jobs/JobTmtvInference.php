<?php

namespace App\Jobs;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\GaelOProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class JobTmtvInference implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;
    public $timeout = 300;
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GaelOProcessingService $gaelOProcessingService, FrameworkInterface $frameworkInterface): void
    {
        $orthancSeriesIdPt ='40f008c4-18e01723-3bf8793d-5e1d2cfb-af1b3802';
        $idPT = $gaelOProcessingService->createSeriesFromOrthanc($orthancSeriesIdPt, true, true);
        $idCT = $gaelOProcessingService->createSeriesFromOrthanc('8460a711-e055e4b2-1747def1-0db79fdf-f33d2944');
        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $gaelOProcessingService->executeInference('unet_model', $inferencePayload);

        $mipPayload = ['min' => 0, 'max' => 10, 'inverted' => true, 'orientation' => 'LPI'];
        $mipPT = $gaelOProcessingService->createMIPForSeries($idPT, $mipPayload);
        $frameworkInterface->storeFile('PETMipTest.gif', fopen($mipPT, 'r'));

        $maskId = $inferenceResponse['id_mask'];
        $mipPayload = ['maskId' => $maskId, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipPayload);
        $frameworkInterface->storeFile('InferenceTest.gif', fopen($mipMask, 'r'));
        $niftiMask = $gaelOProcessingService->getNiftiMask($maskId);
        $frameworkInterface->storeFile('mask.nii.gz', fopen($niftiMask, 'r'));
        $imageMask = $gaelOProcessingService->getNiftiSeries($idPT);
        $frameworkInterface->storeFile('pet.nii.gz', fopen($imageMask, 'r'));
        Log::alert($inferenceResponse);
        #Do Mask Fragmentation
        $fragmentedMaskId = $gaelOProcessingService->fragmentMask($idPT, $maskId);
        $fragmentedNiftiMask = $gaelOProcessingService->getNiftiMask($fragmentedMaskId);
        $frameworkInterface->storeFile('mask_fragmented.nii.gz', fopen($fragmentedNiftiMask, 'r'));

        #Fragmented Mip
        $mipFragmentedPayload = ['maskId' => $fragmentedMaskId, 'min' => 0, 'max' => 5, 'inverted' => true, 'orientation' => 'LPI'];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipFragmentedPayload);
        $frameworkInterface->storeFile('fragmentedInferenceTest.gif', fopen($mipMask, 'r'));

        #get Rtss
        $rtssId = $gaelOProcessingService->createRtssFromMask($orthancSeriesIdPt, $fragmentedMaskId);
        $rtssFile = $gaelOProcessingService->getRtss($rtssId);
        $frameworkInterface->storeFile('rtss.dcm', fopen($rtssFile, 'r'));

        #get Seg
        $segId = $gaelOProcessingService->createSegFromMask($orthancSeriesIdPt, $fragmentedMaskId);
        $segFile = $gaelOProcessingService->getSeg($segId);
        $frameworkInterface->storeFile('seg.dcm', fopen($segFile, 'r'));

        #get Nifti Dicom
        $maskdicom = $gaelOProcessingService->getMaskDicomOrientation($fragmentedMaskId, 'LPI', false);
        $frameworkInterface->storeFile('mask_dicom.nii', fopen($maskdicom, 'r'));

        #get Stats
        $stats = $gaelOProcessingService->getStatsMask($maskId);
        Log::info($stats);
    }

    private function deleteRessources(){
        //Todo supprimer la series, le mask, le mask fragmente, le rtss, le seg et le dicom cache
    }
}

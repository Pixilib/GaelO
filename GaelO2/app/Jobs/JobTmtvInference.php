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
        $idPT = $gaelOProcessingService->createSeriesFromOrthanc('5c20b778-711476a9-a29d98c1-8ddf2357-65f3f13f', true, true);
        $idCT = $gaelOProcessingService->createSeriesFromOrthanc('dd05d0fa-c9984728-eaf4bd15-8420aef2-55257408');
        $inferencePayload = [
            'idPT' => $idPT,
            'idCT' => $idCT
        ];
        $inferenceResponse = $gaelOProcessingService->executeInference('unet_model', $inferencePayload);
        $maskId = $inferenceResponse['id_mask'];
        $mipPayload = ['maskId' => $maskId, 'min' => 0, 'max' => 30];
        $mipMask = $gaelOProcessingService->createMIPForSeries($idPT, $mipPayload);
        $frameworkInterface->storeFile('InferenceTest.gif', fopen($mipMask, 'r'));
        $niftiMask = $gaelOProcessingService->getNiftiMask($maskId);
        $frameworkInterface->storeFile('mask.nii.gz', fopen($niftiMask, 'r'));
        $imageMask = $gaelOProcessingService->getNiftiSeries($idPT);
        $frameworkInterface->storeFile('pet.nii.gz', fopen($imageMask, 'r'));
        Log::alert($inferenceResponse);
    }
}

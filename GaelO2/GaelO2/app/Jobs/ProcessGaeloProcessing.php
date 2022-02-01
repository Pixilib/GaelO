<?php

namespace App\Jobs;

use App\Gaelo\Services\AzureService;
use App\GaelO\Services\ProcessingService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGaeloProcessing implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShouldBeUnique;

    private string $orthancSeriesID;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $orthancSeriesID)
    {
        $this->orthancSeriesID = $orthancSeriesID;
        $this->onQueue('GaelOProcessing');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProcessingService $processingService, AzureService $azureService)
    {
        //

        if ($this->batch()->finished()) {
            $azureService->stopACI();
        }
    }
}

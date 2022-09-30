<?php

namespace App\Jobs;

use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Batchable;

class JobGaelOProcessing implements ShouldQueue
{
    use  Batchable, Dispatchable, InteractsWithQueue, Queueable;

    private array $orthancSeriesID;
    private string $processingName;
    private string $host;
    public $timeout = 600;
    public $failOnTimeout = true;

    /**
     * Create a new processing job instance.
     *
     * @return void
     */
    public function __construct(array  $orthancSeriesID, string $processingName, string $host)
    {
        $this->onQueue('processing');
        $this->orthancSeriesID = $orthancSeriesID;
        $this->processingName = $processingName;
        $this->host = $host;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GaelOProcessingService $gaelOProcessingService)
    {
        $gaelOProcessingService->setHost($this->host);
        $gaelOProcessingService->sendDicom($this->orthancSeriesID);
    }
}

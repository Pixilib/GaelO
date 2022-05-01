<?php

namespace App\Jobs;

use App\GaelO\Services\GaelOProcessingService\AzureService;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoadGaelOProcessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private array $orthancSeriesID;
    private string $processingName;
    public $timeout = 1200;

    /**
     * Job to start ACI and add processing job to bach processing
     */
    public function __construct(array  $orthancSeriesID, string $processingName)
    {
        $this->orthancSeriesID = $orthancSeriesID;
        $this->processingName = $processingName;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AzureService $azureService)
    {

        //If ACI is not running, start and wait for initialization
        if (!$azureService->isRunning()) {
            $azureService->startAndWait();
        };

        //Get IP of the ACI
        $ip = $azureService->getIP();

        Bus::batch(
            [
                new JobGaelOProcessing(
                    $this->orthancSeriesID,
                    $this->processingName,
                    $ip
                )
            ]
        )->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            //SK TODO : Something wrong in the batch of job send email to administrator
            Log::info($e);
        })->finally(function (Batch $batch) {
            $azureService = App::make(AzureService::class);
            $azureService->stopAciAndWait();
            $azureService->isStopped();
        })
        ->allowFailures()
        ->dispatch();
    }

    /**
     * In case of failure of the current job stop ACI
     */
    private function failed(Throwable $exception)
    {
        $azureService = App::make(AzureService::class);
        $azureService->stopAci();
    }
}

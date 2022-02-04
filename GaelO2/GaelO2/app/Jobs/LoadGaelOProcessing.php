<?php

namespace App\Jobs;

use App\Gaelo\Services\AzureService;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Throwable;

class LoadGaelOProcessing implements ShouldQueue
{
    use  Dispatchable, InteractsWithQueue, Queueable;

    private array  $orthancSeriesID;
    private string $processingName;

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

        //Si ACI ETTEINTE => START AND WAIT
        if( ! $azureService->isRunning()){
            $azureService->startAndWait();
        };


        //Recuperere l'IP
        $ip = $azureService->getIP();


        //
        /* getorthancID et un getProcessingName*/

        /*
        *Passage des job ici donc on rentré l'ip ici 
        *set ip 
        */
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
                //=> AZURE SERVICE STOP AND WAIT => auzre service stop and wait
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
                //ALERTE MAIL + stop azurz
            })->finally(function (Batch $batch) {
                //?
            })->dispatch();

        
    }
}

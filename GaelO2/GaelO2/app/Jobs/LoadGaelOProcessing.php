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
use Illuminate\Support\Facades\Log;
use Throwable;

class LoadGaelOProcessing implements ShouldQueue
{
    use  Dispatchable, InteractsWithQueue, Queueable;

    private array  $orthancSeriesID;
    private string $processingName;
    public $timeout = 1200;
       /*
        *fonction qui recupere l'orthancID
        *fonction qui recupere le processingname 
        *pour le passé dans les job
        *ou passé par un service qui s'en charge 
        **/
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
        /*
        *fonction qui recupere l'orthancID
        *fonction qui recupere le processingname 
        *pour le passé dans les job
        **/
        Log::info("je suis l ip");
        Log::info($ip);
        $azureService->stopAci();
/*
        Bus::batch(
                [
                    new JobGaelOProcessing(
                        $this->orthancSeriesID,
                        $this->processingName,
                        $ip
                    )
                ]
            )->then(function (Batch $batch) {
                Log::info("je suis dans le then");
                // All jobs completed successfully...
              // $azureService->stopAciAndWait();
               //$azureService->isStopped();
            })->catch(function (Batch $batch, Throwable $e) {
                //ALERTE MAIL admin pk pas avec log 
                Log::info("je suis dans le catch");
            })->finally(function (Batch $batch) {
                Log::info("je suis dans le finally");
                $azureService->stopAciAndWait();
                $azureService->isStopped();
            })->allowFailures()->dispatch();
 */    
    }
    private function failed(Throwable $exception)
    {
        Log::info("je suis dans le failed");
        //meme fonction sandMail que dans le catch 
           $azureService->stopAci();
    }
}

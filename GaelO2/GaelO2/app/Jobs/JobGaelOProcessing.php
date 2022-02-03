<?php

namespace App\Jobs;

use App\GaelO\Services\GaelOProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobGaeloProcessing implements ShouldQueue
{
    use  Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShouldBeUnique;

    /*
    *Creation des job qui seront envoyer dans le load
    */
    
 
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $orthancSeriesID, string $processingName,string $ip)
    {
        $this->orthancSeriesID = $orthancSeriesID;
        $this->processingName = $processingName;
        $this->ip=$ip;
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
   

    public function handle(GaelOProcessingService $GaelOProcessingService)
    {
        
        //l'ip arrive  pour l'adresse 
        
        gaeloProcessingService->setUrl('http://'.$ip.':8000');

        GaelOProcessingService->sendDicom($orthancSeriesID);
        
    }
}

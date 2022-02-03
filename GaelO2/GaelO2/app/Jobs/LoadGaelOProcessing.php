<?php

namespace App\Jobs;

use App\Gaelo\Services\AzureService;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadGaelOProcessing implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShouldBeUnique;

    /*
    * Gestionnaire de queue 
    */
   
    public int $timeout=1800;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(  )
    {
      
    }

   

    }
    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        //
       
        $this->AzureService->isRunning();
        $this->AzureService->getIP();
        
        /*
        *Passage des job ici donc on rentré l'ip ici 
        *set ip 
        */
        $batch = Bus::batch 
        ([new JobGaelOProcessing("717b834e-a4e89074-51018c12-59e12ebd-598a673f",'Nimportequoi',$ip)]);
        $this->AzureService->isStopped();
     
    }
}

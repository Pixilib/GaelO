<?php

namespace App\Jobs;

use App\Gaelo\Services\AzureService;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadGaelOProcessing implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
    * Gestionnaire de queue 
    */
   
    public int $timeout=1800;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( )
    {
    
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        /* getorthancID et un getProcessingName*/
     
        /*
        *Passage des job ici donc on rentré l'ip ici 
        *set ip 
        */
        $batch = Bus::batch 
        ([new JobGaelOProcessing(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"],'Nimportequoi','
        51.138.216.115
        ')]);
    
        $this->AzureService->checkstatus();
     
    }
}

<?php

namespace App\Jobs;

use App\GaelO\Services\GaelOProcessingService;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

class JobGaeloProcessing implements ShouldQueue
{
    use  Batchable,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
    *Creation des job qui seront envoyer dans le load
    */
    
   
    private $orthancSeriesID ="717b834e-a4e89074-51018c12-59e12ebd-598a673f";
    private $processingName='Nimportequoi';
    private $ip='20.74.32.229';
    
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
   

    public function handle(GaelOProcessingService $gaelOProcessingService ,HttpClientInterface $httpClientInterface)
    {
        
        //l'ip arrive  pour l'adresse 
        
         $httpClientInterface->setUrl('http://'.'20.74.32.229'.':8000');
         $gaelOProcessingService->sendDicom(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"]);   
        
    }
}

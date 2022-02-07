<?php

namespace App\Jobs;

use App\GaelO\Services\GaelOProcessingService;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

class JobGaelOProcessing implements ShouldQueue
{
    use  Batchable, Dispatchable, InteractsWithQueue, Queueable;

    
    /*
    *Creation des job qui seront envoyer dans le load
    */
    
   
    private array $orthancSeriesID;
    private string $processingName;
    private string $url;
    public $timeout = 600;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array  $orthancSeriesID, string $processingName, string $url)
    {
        $this->orthancSeriesID = $orthancSeriesID;
        $this->processingName = $processingName;
        $this->url=$url;
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
   

    public function handle(GaelOProcessingService $gaelOProcessingService )
    {  
        //l'ip arrive  pour l'adresse 
        $gaelOProcessingService->setServerAdress($this->url);
        $gaelOProcessingService->sendDicom($this->orthancSeriesID);  

        
    }
}

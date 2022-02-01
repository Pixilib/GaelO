<?php

namespace Tests\Unit\TestServices;

use Tests\TestCase;
use App\GaelO\Services\ProcessingService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ProcessingServiceTest extends TestCase
{
    private ProcessingService $processingService;
    
    protected function setUp():void{

        parent::setUp();
        $this->processingService = App::make(ProcessingService::class);     
    }
    
    public function testSendDicom()
    {
        $resultat=$this->processingService->sendDicom(["717b834e-a4e89074-51018c12-59e12ebd-598a673f"]);
        Log::info($resultat);
        //$this->assertNotEmpty($resultat);
    }
}

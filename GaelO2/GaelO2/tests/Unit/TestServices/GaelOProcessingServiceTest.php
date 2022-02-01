<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOProcessingService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GaelOProcessingServiceTest extends TestCase
{
    private GaelOProcessingService $gaeloProcessingService;

    protected function setUp():void{

        parent::setUp();
        $this->gaeloProcessingService = App::make(GaelOProcessingService::class);
    }

    public function testSendDicom()
    {
        $resultat=$this->gaeloProcessingService->sendDicom(["717b834e-a4e89074-51018c12-59e12ebd-598a673f"]);
        Log::info($resultat);
        //$this->assertNotEmpty($resultat);
    }
}

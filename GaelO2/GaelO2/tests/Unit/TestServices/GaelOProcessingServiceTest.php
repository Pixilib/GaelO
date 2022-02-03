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
        $resultat=$this->gaeloProcessingService->sendDicom(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"]);
        Log::info($resultat);
        //$this->assertNotEmpty($resultat);
    }
}

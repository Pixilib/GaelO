<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOWsiProcessingService\GaelOWsiProcessingService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;

class GaelOWsiProcessingServiceTest extends TestCase
{
    private GaelOWsiProcessingService $gaeloWsiProcessingService;

    protected function setUp():void{

        parent::setUp();
        $this->gaeloWsiProcessingService = App::make(GaelOWsiProcessingService::class);
        // $this->markTestSkipped();
    }

    public function testWelcome()
    {
        $resultat=$this->gaeloWsiProcessingService->getWelcomeGaeloWsiProcessing();
        $resultat->assertStatus(200);
    }
}

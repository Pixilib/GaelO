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
    }

    public function testWelcome()
    {
        // Call the service method
        $resultat = $this->gaeloWsiProcessingService->getWelcomeGaeloWsiProcessing();

        // Assert the status code and the response body
        $this->assertEquals(200, $resultat->getStatusCode());
        $this->assertEquals('Welcome to GaelO Pathology Processing Backend !', $resultat->getBody());
    }
}


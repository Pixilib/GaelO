<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;

class GaelOProcessingServiceTest extends TestCase
{
    private GaelOProcessingService $gaeloProcessingService;

    protected function setUp():void{

        parent::setUp();
        $this->gaeloProcessingService = App::make(GaelOProcessingService::class);
        $this->markTestSkipped();
    }

    public function testSendDicom()
    {
        $path = getcwd() . "/tests/data/MR.zip";
        $resultat=$this->gaeloProcessingService->createDicom($path);
    }
}

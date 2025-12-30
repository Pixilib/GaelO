<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOProcessingService\GaelOProcessingService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GaelOProcessingServiceTest extends TestCase
{
    private GaelOProcessingService $gaeloProcessingService;

    protected function setUp():void{

        parent::setUp();
        $this->gaeloProcessingService = App::make(GaelOProcessingService::class);
        $this->markTestSkipped();
    }

    public function testGetRtss(){
        $resultat=$this->gaeloProcessingService->getRtss("589201abe0658bbfd56a381c0cf782f1");
    }

    public function testGetSeg(){
        $resultat=$this->gaeloProcessingService->getSeg("589201abe0658bbfd56a381c0cf782f1");
    }

    
    public function testSendDicom()
    {
        $path = getcwd() . "/tests/data/MR.zip";
        $resultat=$this->gaeloProcessingService->createDicom($path);
    }
}

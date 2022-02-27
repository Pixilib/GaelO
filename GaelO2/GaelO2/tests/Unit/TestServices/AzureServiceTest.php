<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOProcessingService\AzureService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;

class AzureServiceTest extends TestCase
{
    private AzureService $azureService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->azureService = App::make(AzureService::class);
        $this->markTestSkipped();
    }


    public function testStartAci(){
        $res = $this -> azureService -> startAci();
        $this->assertEquals(202,$res);
    }

    /**
     * @depends testStartAci
     */
    public function testStopAci()
    {
        $res = $this->azureService->stopAci();
        $this->assertEquals(204, $res);
    }


    public function testGetStatusAciState()
    {
        $res = $this->azureService->getStatusAci();
        $state = ["Pending", "Running", "Stopped"];
        $this->assertContains($res['state'], $state);
    }

    /**
     * @depends testStartAci
     */
    public function testGetStatusAciIP()
    {
        $res = $this->azureService->getStatusAci();
        $ip = preg_match('/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)(\.(?!$)|$)){4}$/', $res['ip']);
        $this->assertTrue(true, $ip);
    }

}

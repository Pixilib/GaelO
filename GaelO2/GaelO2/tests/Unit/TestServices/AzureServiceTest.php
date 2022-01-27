<?php

namespace Tests\Unit\TestServices;

use Tests\TestCase;
use App\GaelO\Services\AzureService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class AzureServiceTest extends TestCase
{
    private AzureService $azureService;

    protected function setUp():void {

        parent::setUp();

        $this ->azureService = App::make(AzureService::class);
    }

  /*  public function testGetTokenAzure()
    {
        $res = $this -> azureService -> getTokenAzure();
        Log::info($res);
    } 
*/
    public function testGetStatusAci(){
        $res = $this -> azureService -> getStatusAci();
        log::info($res);
    }
     /*  
      public function testSetToken(){
        $res = $this -> azureService -> setToken();
        log::info($res);
    }*/
}
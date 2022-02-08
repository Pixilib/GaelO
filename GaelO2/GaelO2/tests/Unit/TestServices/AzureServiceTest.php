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

 

     /*  
      public function testSetToken(){
        $res = $this -> azureService -> setToken();
        log::info($res);
    }*/
    /* 
    public function testStartAci(){
        $res = $this -> azureService -> startAci();
        Log::info('j allume azure');
        Log::info($res);
        $this->assertEquals(202,$res );
    }
   
 */
    public function testStopAci(){
        $res = $this -> azureService -> stopAci();
        $this->assertEquals(204 , $res);
    }
 

    public function testGetStatusAciState(){

    $res = $this -> azureService -> getStatusAci();
    Log::info($res['state']);
    $state = ["Pending","Running","Stopped"];
    $this->assertContains($res['state'],$state);
    }

    public function testGetStatusAciIP(){

    $res = $this -> azureService -> getStatusAci();
    $ip = preg_match('/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)(\.(?!$)|$)){4}$/', $res['ip']);
    $this->assertTrue(true,$ip);
    }

    /*
    public function testCheckStatus(){
    $res=$this-> azureService ->checkStatus();
    } 
 */
}
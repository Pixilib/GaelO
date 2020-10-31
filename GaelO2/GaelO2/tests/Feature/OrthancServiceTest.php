<?php

namespace Tests\Feature;

use App\GaelO\Services\OrthancService;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class OrthancServiceTest extends TestCase
{

    protected function setUp() : void{
        parent::setUp();
        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        if (false) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOrthancConnexion()
    {
       $answer = $this->orthancService->getOrthancPeers();
       $this->assertIsArray($answer);
    }

    public function testOrthancAddPeers()
    {
        $answer = $this->orthancService->addPeer('gaelotest', 'http://kanoun.fr:8043', 'salim', 'salim');
        $statusCode = $answer->getStatusCode();
        $this->assertEquals(200, $statusCode);
    }

    public function testOrthancPeerIsTransferAccelerated(){
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $bool = $this->orthancService->isPeerAccelerated('gaelotest');
    }

    public function testOrthancSendToPeer(){
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $response = $this->orthancService->sendToPeer('gaelotest', [''], true);
    }

    public function testOrthancDeletePeers(){

        $answer = $this->orthancService->deletePeer('gaelotest');
        $statusCode = $answer->getStatusCode();
        $this->assertEquals(200, $statusCode);

    }

    public function testOrthancRemoveAllPeers(){

        $this->orthancService->removeAllPeers();
        $peers = $this->orthancService->getOrthancPeers();
        $this->assertEmpty($peers);

    }


}

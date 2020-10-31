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

    public function testSendDicomFile(){
        $path = "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm";
        $answer = $this->orthancService->importFile($path);
        $this->assertArrayHasKey("ParentStudy", $answer);
    }

    public function testSendDicomFileArray(){
        $array = [
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm",
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0b7033a437f446e28a999d79ca9901ef.dcm"
        ];
        $answer = $this->orthancService->importFiles($array);
        $this->assertEquals(2, sizeof($answer));
    }


}

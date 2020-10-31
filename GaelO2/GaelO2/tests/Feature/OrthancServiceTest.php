<?php

namespace Tests\Feature;

use App\GaelO\Services\OrthancService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class OrthancServiceTest extends TestCase
{

    protected function setUp() : void{
        parent::setUp();
        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        if (true) {
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

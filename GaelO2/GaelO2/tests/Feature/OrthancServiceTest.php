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
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOrthancConnexion()
    {
        $answerStatusCode = $this->orthancService->getOrthancPeers()->getStatusCode();
        $this->assertEquals(200, $answerStatusCode);
    }

    public function testOrthancAddPeers()
    {
        $answer = $this->orthancService->addPeer('testano', 'http://localhost:8043', 'salim', 'salim');
        dd($answer);
    }
}

<?php

namespace Tests\Feature;

use App\GaelO\Services\OrthancService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class OrthancServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOrthancConnexion()
    {
        $orthancService = App::make(OrthancService::class);
        $orthancService->setOrthancServer(false);

        $answer = $orthancService->getOrthancPeers();
    }
}

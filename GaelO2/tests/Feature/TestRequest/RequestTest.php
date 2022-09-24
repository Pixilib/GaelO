<?php

namespace Tests\Feature\TestRequest;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Test Send Request API
     *
     * @return void
     */
    public function testRequest()
    {
        $data = ['email' => 'salim.kanoun@gmail.com',
        'center' => 'toulouse',
        'name' => 'truc',
        'request' => 'fgfdgfgfdgfdgfdg'];

       $this->json('POST', '/api/request', $data)->assertSuccessful();
    }

    public function testIncompleteRequest(){

        $data = ['center' => 'toulouse',
        'name' => 'truc',
        'request' => 'fgfdgfgfdgfdgfdg'];

        $answer = $this->json('POST', '/api/request', $data);
        $answer-> assertStatus(400);
        $answer-> assertJsonStructure(["errorMessage"]);

    }

}

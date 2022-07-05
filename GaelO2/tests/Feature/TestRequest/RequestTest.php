<?php

namespace Tests\Feature\TestRequest;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RequestTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
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

    public function testSystemApi(){
        $answer = $this->json('GET', '/api/system');
        $answer-> assertStatus(200);
        $answer-> assertJsonStructure(["version"]);
    }
}

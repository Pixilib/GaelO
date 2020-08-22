<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
     * A basic feature test example.
     *
     * @return void
     */
    public function testRequest()
    {
        $data = ['email' => 'salim.kanoun@gmail.com',
        'center' => 'toulouse',
        'name' => 'truc',
        'request' => 'fgfdgfgfdgfdgfdg'];

        //Test user creation
        $response = $this->json('POST', '/api/request', $data)-> assertSuccessful();
    }
}

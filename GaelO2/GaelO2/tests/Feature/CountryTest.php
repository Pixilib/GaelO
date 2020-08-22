<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CountryTest extends TestCase
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

    public function testGetCountry()
    {
        $response = $this->json('GET', '/api/countries/FR')->assertSuccessful()->assertJson(['code'=>'FR']);
        $response = $this->json('GET', '/api/countries') -> decodeResponseJson();
        $this->assertEquals( 255,  sizeof($response) );
        $response = $this->json('GET', '/api/countries/WrongCountry') -> assertStatus(404);
    }
}

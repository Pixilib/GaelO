<?php

namespace Tests\Feature\TestCountry;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\AuthorizationTools;

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

    protected function setUp() : void{
        parent::setUp();
        Artisan::call('passport:install');
    }

    public function testGetCountry()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('GET', '/api/countries/FR')->assertJson(['code'=>'FR']);
        $response = $this->json('GET', '/api/countries') -> decodeResponseJson();
        $this->assertEquals( 255,  sizeof($response) );
    }

    public function testGetCountryShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/countries/FR')->assertStatus(403);
    }
}

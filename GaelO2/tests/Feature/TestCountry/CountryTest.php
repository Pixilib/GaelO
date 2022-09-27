<?php

namespace Tests\Feature\TestCountry;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
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

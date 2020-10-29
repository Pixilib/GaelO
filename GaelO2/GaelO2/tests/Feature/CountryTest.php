<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

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
        Passport::actingAs(
            User::where('id',1)->first()
        );

    }

    public function testGetCountry()
    {
        $this->json('GET', '/api/countries/FR')->assertJson(['code'=>'FR']);
        $response = $this->json('GET', '/api/countries') -> decodeResponseJson();
        $this->assertEquals( 255,  sizeof($response) );
        $this->json('GET', '/api/countries/WrongCountry') -> assertStatus(404);
    }
}

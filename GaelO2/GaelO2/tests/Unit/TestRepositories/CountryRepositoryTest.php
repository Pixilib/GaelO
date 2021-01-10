<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\CountryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Country;

class CountryRepositoryTest extends TestCase
{
    private CountryRepository $countryRepository;

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->countryRepository = new CountryRepository(new Country());
    }

    public function testGetCountryByCode(){

        $country = $this->countryRepository->getCountryByCode('FR');
        $this->assertArrayHasKey('code', $country);
        $this->assertArrayHasKey('country_us', $country);
        $this->assertArrayHasKey('country_fr', $country);

    }

    public function testGetAllCountries(){
        $countries = $this->countryRepository->getAllCountries();
        $this->assertEquals(255, sizeof($countries));
    }


}

<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\CountryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Country;

class CountryRepositoryTest extends TestCase
{
    private CountryRepository $countryRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
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

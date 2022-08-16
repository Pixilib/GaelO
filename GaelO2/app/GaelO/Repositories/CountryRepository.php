<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\CountryRepositoryInterface;
use App\Models\Country;

class CountryRepository implements CountryRepositoryInterface
{

    private Country $countryModel;

    public function __construct(Country $country)
    {
        $this->countryModel = $country;
    }

    public function getCountryByCode($code)
    {
        return $this->countryModel->findOrFail($code)->toArray();
    }

    public function getAllCountries()
    {
        $countries = $this->countryModel->get();
        return empty($countries) ? []  : $countries->sortBy('country_us')->toArray();
    }
}

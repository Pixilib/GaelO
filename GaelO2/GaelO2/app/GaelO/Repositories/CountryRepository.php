<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\Repositories\CountryRepositoryInterface;
use App\Models\Country;

class CountryRepository implements CountryRepositoryInterface
{

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function getCountryByCode($code)
    {
        return $this->country->findOrFail($code)->toArray();
    }

    public function getAllCountries()
    {
        $countries = $this->country->get();
        return empty($countries) ? []  : $countries->sortBy('country_us')->toArray();
    }
}

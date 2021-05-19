<?php

namespace App\GaelO\Interfaces\Repositories;

interface CountryRepositoryInterface {

    public function getCountryByCode($code);

    public function getAllCountries();
}

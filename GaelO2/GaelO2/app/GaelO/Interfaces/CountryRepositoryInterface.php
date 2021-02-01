<?php

namespace App\GaelO\Interfaces;

interface CountryRepositoryInterface {

    public function getCountryByCode($code);

    public function getAllCountries();
}

<?php

namespace App\GaelO\Entities;

class CountryEntity {
    public string $code;
    public string $countryUs;
    public string $countryFr;

    public static function fillFromDBReponseArray(array $array){
        $countryEntity  = new CountryEntity();
        $countryEntity->code = $array['code'];
        $countryEntity->countryUs = $array['country_us'];
        $countryEntity->countryFr = $array['country_fr'];

        return $countryEntity;
    }
}

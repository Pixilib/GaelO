<?php

namespace App\GaelO\UseCases\GetCenter;

class CenterEntity {
    public int $code;
    public string $name;
    public string $countryCode;

    public static function fillFromDBReponseArray(array $array){
        $countryEntity  = new CenterEntity();
        $countryEntity->code = $array['code'];
        $countryEntity->name = $array['name'];
        $countryEntity->countryCode = $array['country_code'];

        return $countryEntity;
    }

}

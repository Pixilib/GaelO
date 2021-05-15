<?php

namespace App\GaelO\Entities;

class CenterEntity {
    public int $code;
    public string $name;
    public string $countryCode;

    public static function fillFromDBReponseArray(array $array){
        $centerEntity  = new CenterEntity();
        $centerEntity->code = $array['code'];
        $centerEntity->name = $array['name'];
        $centerEntity->countryCode = $array['country_code'];

        return $centerEntity;
    }
}

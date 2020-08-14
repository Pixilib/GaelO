<?php

namespace App\GaelO;

class Util {

    public static function fillObject (array $dataToExtract, object $dataToFill) {
        var_dump($dataToExtract);
        foreach($dataToExtract as $property => $value) {
            var_dump($property);
            var_dump($value);
            $dataToFill->$property = $dataToExtract[$property];
        } 
        return $dataToFill;
    }

    public static function now() {
        return now()->toDateTimeString();
    }
}

?>
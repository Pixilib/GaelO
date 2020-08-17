<?php

namespace App\GaelO;

class Util {

    public static function fillObject (array $dataToExtract, object $dataToFill) {
        foreach($dataToExtract as $property => $value) {
            $dataToFill->$property = $dataToExtract[$property];
        } 
        return $dataToFill;
    }

    public static function now() {
        return now()->toDateTimeString();
    }
}

?>
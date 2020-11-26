<?php

namespace App\GaelO;

use Carbon\Carbon;

class Util {

    public static function fillObject (array $dataToExtract, object $dataToFill) {
        foreach($dataToExtract as $property => $value) {
            if (isset($value)) $dataToFill->$property = $dataToExtract[$property];
            else $dataToFill->$property = null;
        }
        return $dataToFill;
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    public static function now() {
        return Carbon::now()->format('Y-m-d H:i:s.u');
    }

    public static function camelCaseToSnakeCase(string $string, string $us = "_")  : string {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string));
    }
}

?>

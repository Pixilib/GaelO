<?php

namespace App\GaelO;

use DateTime;

class DicomUtils
{

    public static function filterMicrosecInDicomTime(?string $string): ?string
    {

        if (strpos($string, ".")) {
            $timeWithoutms = explode(".", $string);
            $string = $timeWithoutms[0];
        }

        return $string;
    }

    public static function parseDicomDateTime(?string $string, string $outputFormat = 'Y-m-d H:i:s') : ?string
    {
        $parsedDateTime = null;

        $string = self::filterMicrosecInDicomTime($string);

        $dateObject = DateTime::createFromFormat('YmdHis', $string);
        if ($dateObject !== false) {
            $parsedDateTime = $dateObject->format($outputFormat);
        }

        return $parsedDateTime;
    }

    public static function parseDicomTime(?string $string, string $outputFormat = 'H:i:s') : ?string
    {
        $parsedDateTime = null;

        $string = self::filterMicrosecInDicomTime($string);

        $dateObject = DateTime::createFromFormat('His', $string);
        if ($dateObject !== false) {
            $parsedDateTime = $dateObject->format($outputFormat);
        }

        return $parsedDateTime;
    }

    public static function parseDicomDate(?string $string, string $outputFormat = 'Y-m-d') : ?string
    {
        $parsedDateTime = null;

        $dateObject = DateTime::createFromFormat('Ymd', $string);
        if ($dateObject !== false) {
            $parsedDateTime = $dateObject->format($outputFormat);
        }

        return $parsedDateTime;
    }

}

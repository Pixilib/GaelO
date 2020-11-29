<?php

namespace App\GaelO;

use App\GaelO\Exceptions\GaelOBadRequestException;
use Carbon\Carbon;
use DateTime;
use Exception;

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

    function snakeCaseToCamelCase(string $string) : string {
        $str = str_replace('_', '', ucwords($string, '_'));
        $str = lcfirst($str);
        return $str;
    }

    	/**
	 * Format registration date according to plateform preference (french or US format)
	 * @param string registrationDate
	 * @return String
	 */
	public static function formatUSDateStringToSQLDateFormat(string $registrationDate) : String {
		$dateNbArray=explode('/', $registrationDate);
        $registrationDay=intval($dateNbArray[1]);
        $registrationMonth=intval($dateNbArray[0]);
        $registrationYear=intval($dateNbArray[2]);

		if ($registrationDay == 0 || $registrationMonth == 0 || $registrationYear == 0) {
			throw new GaelOBadRequestException('Wrong Registration Date');
		}

		try {
			$dateResult=new DateTime($registrationYear.'-'.$registrationMonth.'-'.$registrationDay);
            return $dateResult->format('Y-m-d');
        }catch (Exception $e) {
			throw new GaelOBadRequestException('Wrong Registration Date');
		}

    }
}

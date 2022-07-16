<?php

namespace App\GaelO;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Exceptions\GaelOBadRequestException;
use Carbon\Carbon;
use DateTime;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ZipArchive;

class Util
{

    public static function fillObject(array $dataToExtract, object $dataToFill)
    {
        //Get Expected properties awaited in DTO Request
        $reflect = new ReflectionClass($dataToFill);
        $awaitedProperties = $reflect->getProperties();

        //Loop these properties and fill it with incoming data is present
        foreach ($awaitedProperties as $property) {
            $propertyName = $property->getName();
            if (array_key_exists($propertyName, $dataToExtract)) $dataToFill->$propertyName = $dataToExtract[$propertyName];
        }
        return $dataToFill;
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    public static function now()
    {
        return Carbon::now()->format('Y-m-d H:i:s.u');
    }

    public static function camelCaseToSnakeCase(string $string, string $us = "_"): string
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
            $us,
            $string
        ));
    }

    function snakeCaseToCamelCase(string $string): string
    {
        $str = str_replace('_', '', ucwords($string, '_'));
        $str = lcfirst($str);
        return $str;
    }

    /**
     * Format registration date according to plateform preference (french or US format)
     * @param string registrationDate
     * @return String
     */
    public static function formatUSDateStringToSQLDateFormat(string $registrationDate): String
    {
        $dateNbArray = explode('/', $registrationDate);
        $registrationDay = intval($dateNbArray[1]);
        $registrationMonth = intval($dateNbArray[0]);
        $registrationYear = intval($dateNbArray[2]);

        if ($registrationDay == 0 || $registrationMonth == 0 || $registrationYear == 0) {
            throw new GaelOBadRequestException('Wrong Registration Date');
        }

        try {
            $dateResult = new DateTime($registrationYear . '-' . $registrationMonth . '-' . $registrationDay);
            return $dateResult->format('Y-m-d');
        } catch (Exception $e) {
            throw new GaelOBadRequestException('Wrong Registration Date');
        }
    }


    /**
     * Check Password constraints :
     * Should have length at least 8 characters
     * Should have at least a different case
     * Can have special characters like !@#$%^&*()\[]{}-_+=~`|:;'<>,./?
     */
    public static function checkPasswordFormatCorrect(string $password)
    {
        $checkOneDigit = "(?=.*\d)";
        $checkOneLowerCase = "(?=.*[a-z])";
        $checkOneUpperCase = "(?=.*[A-Z])";
        $checkStrContent = "[0-9A-Za-z\!@#$%^&*()\\[\]{}\-_+=~`|:;'<>,.\/?]"; //Allow for special char
        $checkLength = "{8,}";
        $wholeStringCheck = $checkOneDigit . $checkOneLowerCase . $checkOneUpperCase . $checkStrContent . $checkLength;
        return preg_match('/^' . $wholeStringCheck . '$/', $password);
    }

    public static function addStoredFilesInZip(ZipArchive $zip, ?string $path)
    {

        $files = FrameworkAdapter::getStoredFiles($path);

        foreach ($files as $file) {
            // Add current file to archive
            $fileContent = FrameworkAdapter::getFile($file);
            $zip->addFromString($file, $fileContent);
        }
    }

    public static function recursiveDirectoryDelete(string $directory)
    {
        $it = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($directory);
    }

    public static function getZipUncompressedSize(string $filename)
    {
        $size = 0;

        $zipArchive = new ZipArchive;
        $zipArchive->open($filename);

        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            $stat = $zipArchive->statIndex($i);
            $size += $stat['size'];
        }

        $zipArchive->close();

        return $size;
    }

    public static function getPathAsFileArray($directory): array
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $files = [];

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            $files[] = $file->getPathname();
        }

        return $files;
    }

    public static function isBase64Encoded($data): bool
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isVersionHigher($current, $previous): bool
    {
        return version_compare($previous, $current, '<');
    }
}

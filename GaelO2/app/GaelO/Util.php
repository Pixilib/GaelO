<?php

namespace App\GaelO;

use App\GaelO\Adapters\FrameworkAdapter;
use Carbon\Carbon;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ZipArchive;

class Util
{

    public static function fillObject(array $dataToExtract, object $dataToFill): void
    {
        //Get Expected properties awaited in DTO Request
        $reflect = new ReflectionClass($dataToFill);
        $awaitedProperties = $reflect->getProperties();

        //Loop these properties and fill it with incoming data is present
        foreach ($awaitedProperties as $property) {
            $propertyName = $property->getName();
            if (array_key_exists($propertyName, $dataToExtract)) $dataToFill->$propertyName = $dataToExtract[$propertyName];
        }
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    public static function now(): string
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

    public static function addStoredFilesInZipAndClose(ZipArchive $zip, ?string $path)
    {
        $temporaryFilesToDelete = [];
        try {
            $files = FrameworkAdapter::getStoredFiles($path);
            foreach ($files as $file) {
                // Add current file to archive using data as stream to prevent running out memory for large files
                $tempraryFilePath = tempnam(ini_get('upload_tmp_dir'), 'TMPEXP_');
                $temporaryFilesToDelete[] = $tempraryFilePath;
                $fileContent = FrameworkAdapter::getFile($file, true);
                stream_copy_to_stream($fileContent, fopen($tempraryFilePath, 'w'));
                $zip->addFile($tempraryFilePath, $file);
            }
            //Close to build the zip as the operation is async
            $zip->close();
        } finally {
            //Delete all temporary files
            foreach ($temporaryFilesToDelete as $temp) {
                unlink($temp);
            }
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


    /**
     * Create and return a tempororary directory path
     */
    public static function getUploadTemporaryFolder(): string
    {
        $unzipedPath = sys_get_temp_dir() . '/GaelO_Upload_' . bin2hex(random_bytes(15));
        mkdir($unzipedPath, 0755);
        return $unzipedPath;
    }

    public static function isVersionHigher($current, $previous): bool
    {
        return version_compare($previous, $current, '<');
    }

    public static function isSemanticVersioning(string $version): bool
    {
        return preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)?$/', $version);
    }

    public static function isUrlSafeString(string $value): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]*$/', $value);
    }
}

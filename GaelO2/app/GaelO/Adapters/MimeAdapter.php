<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\MimeInterface;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class MimeAdapter implements MimeInterface
{

    public static function getExtensionsFromMime(string $mime): array
    {
        $mimes = new ExtensionMimeTypeDetector();
        return $mimes->lookupAllExtensions($mime);
    }

    public static function getMimesFromExtension(string $extension): string
    {
        $mimes = new GeneratedExtensionToMimeTypeMap();
        return $mimes->lookupMimeType($extension);
    }
}

<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\MimeInterface;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class MimeAdapter implements MimeInterface
{

    public static function getExtensionsFromMime(string $mime): array
    {
        if ($mime === 'application/dicom') return ['dcm'];
        $mimes = new ExtensionMimeTypeDetector();
        return $mimes->lookupAllExtensions($mime);
    }

    public static function getMimeFromExtension(string $extension): string
    {
        if ($extension === 'dcm') return 'application/dicom';
        $mimes = new GeneratedExtensionToMimeTypeMap();
        return $mimes->lookupMimeType($extension);
    }
}

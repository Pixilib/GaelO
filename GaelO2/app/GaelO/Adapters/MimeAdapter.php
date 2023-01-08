<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\MimeInterface;
use Mimey\MimeTypes;

class MimeAdapter implements MimeInterface
{

    public static function getExtensionsFromMime(string $mime): array
    {
        $mimes = new MimeTypes();
        return $mimes->getAllExtensions($mime);
    }

    public static function getMimesFromExtension(string $extension): array
    {
        $mimes = new MimeTypes();
        return $mimes->getAllMimeTypes($extension);
    }
}

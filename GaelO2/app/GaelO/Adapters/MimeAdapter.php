<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\MimeInterface;
use Mimey\MimeTypes;

class MimeAdapter implements MimeInterface
{

    public static function getExtensionFromMime(string $mime): string
    {
        $mimes = new MimeTypes();
        return $mimes->getExtension($mime);
    }

    public static function getMimeFromExtension(string $extension): string
    {
        $mimes = new MimeTypes();
        return $mimes->getMimeType($extension);
    }
}

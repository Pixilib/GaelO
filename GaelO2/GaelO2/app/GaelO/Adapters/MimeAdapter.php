<?php

namespace App\GaelO\Adapters;

use Mimey\MimeTypes;

class MimeAdapter
{

    public static function getExtensionFromMime(string $mime) : string
    {
        $mimes = new MimeTypes();
        return $mimes->getExtension($mime);
    }

    public static function getMimeFromExtension(string $extension) : string {
        $mimes = new MimeTypes();
        return $mimes->getMimeType($extension);
    }
}

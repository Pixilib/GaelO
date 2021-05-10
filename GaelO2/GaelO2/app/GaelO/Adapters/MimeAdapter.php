<?php

namespace App\GaelO\Adapters;

use Mimey\MimeTypes;

class MimeAdapter
{

    public static function getExtensionFromMime(string $mime)
    {
        $mimes = new MimeTypes();
        return $mimes->getMimeType($mime);
    }

    public static function getMimeFromExtension(string $extension){
        $mimes = new MimeTypes();
        $mimes->getMimeType($extension);
    }
}

<?php

namespace App\GaelO\Interfaces\Adapters;

Interface MimeInterface{
    public static function getExtensionFromMime(string $mime) : string;
    public static function getMimeFromExtension(string $extension) : string;
}

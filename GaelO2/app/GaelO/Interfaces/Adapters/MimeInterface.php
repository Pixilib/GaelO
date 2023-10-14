<?php

namespace App\GaelO\Interfaces\Adapters;

Interface MimeInterface{
    public static function getExtensionsFromMime(string $mime) : array;
    public static function getMimesFromExtension(string $extension) : array;
}

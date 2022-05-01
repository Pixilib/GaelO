<?php

namespace App\GaelO\Interfaces\Adapters;

interface HashInterface {
    public static function hash(string $password) : string ;

    public static function checkHash(string $plainValue, string $hash) : bool;
}

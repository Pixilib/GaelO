<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\HashInterface;
use Illuminate\Support\Facades\Hash;

class HashAdapter implements HashInterface
{

    public static function hash(string $password): string
    {
        return Hash::make($password);
    }

    public static function checkHash(string $plainValue, string $hash): bool
    {
        return Hash::check($plainValue, $hash);
    }
}

<?php

namespace App\GaelO\Adapters;

use App\User;
use Illuminate\Support\Facades\Hash;

class LaravelFunctionAdapter {
    
    public static function hash (string $password) {
        return Hash::make($password);
    }
}

?>
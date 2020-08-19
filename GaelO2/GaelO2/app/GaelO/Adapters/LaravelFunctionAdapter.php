<?php

namespace App\GaelO\Adapters;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class LaravelFunctionAdapter {

    public static function hash (string $password) {
        return Hash::make($password);
    }

    public static function make(string $className){
        return App::Make($className);
    }
}

?>

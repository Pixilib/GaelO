<?php

namespace App\GaelO\Adapters;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class LaravelFunctionAdapter {

    public static function hash (string $password) {
        return Hash::make($password);
    }

    public static function make(string $className){
        return App::Make($className);
    }

    public static function checkHash(string $plainValue, string $hash){
        return Hash::check($plainValue, $hash);

    }

    public static function getConfig($key){
        return Config::get('app.'.$key);
    }

    public static function getStoragePath(){
        return storage_path();
    }
}

?>

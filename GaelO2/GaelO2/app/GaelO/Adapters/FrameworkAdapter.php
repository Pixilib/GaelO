<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class FrameworkAdapter implements FrameworkInterface {

    public static function make(string $className){
        return App::make($className);
    }

    public static function getConfig(string $key){
        return Config::get('app.'.$key);
    }

    public static function getStoragePath() : string{
        return storage_path().'/gaelo';
    }
}

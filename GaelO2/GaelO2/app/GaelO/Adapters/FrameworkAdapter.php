<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
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

    public static function sendRegisteredEventForEmailVerification(int $userId) {
        $user = User::findOrFail($userId);
        event(new Registered($user));
    }

    public static function resendVerificationEmail(int $userId){
        $user = User::findOrFail($userId);
        $user->sendEmailVerificationNotification();
    }
}

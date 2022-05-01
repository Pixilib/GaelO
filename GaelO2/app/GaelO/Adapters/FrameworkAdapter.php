<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class FrameworkAdapter implements FrameworkInterface
{

    public static function make(string $className)
    {
        return App::make($className);
    }

    public static function getConfig(string $key)
    {
        return Config::get('app.' . $key);
    }

    public static function getStoragePath(): string
    {
        return storage_path() . '/gaelo';
    }

    public static function sendRegisteredEventForEmailVerification(int $userId): void
    {
        $user = User::findOrFail($userId);
        event(new Registered($user));
    }

    public static function sendResetPasswordLink(string $email): bool
    {
        $status = Password::sendResetLink(
            ['email' => $email]
        );

        return $status === Password::RESET_LINK_SENT;
    }


    public static function createMagicLink(int $userId, string $redirectUrl): string
    {
        $routeName = 'magic-link';
        $routeExpires = 72;

        $user = User::find($userId);

        return URL::temporarySignedRoute(
            $routeName,
            now()->addHour($routeExpires),
            [
                'id' => $user->getAuthIdentifier(),
                'redirect_to' => $redirectUrl
            ]
        );

    }
}

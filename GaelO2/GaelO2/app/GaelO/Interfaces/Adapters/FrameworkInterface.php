<?php

namespace App\GaelO\Interfaces\Adapters;

interface FrameworkInterface {

    /**
     * Instanciate class with Depedency injection
     */
    public static function make(string $className);

    /**
     * Config Available Keys are defined in SettingsConstants
     */
    public static function getConfig(string $key);

    /**
     * Get storage path in the project
     */
    public static function getStoragePath() : string ;

    public static function sendResetPasswordLink(string $email) : bool;

    public static function sendRegisteredEventForEmailVerification(int $userId) : void ;

    public static function createMagicLink(int $userId, string $redirectUrl): string;

}

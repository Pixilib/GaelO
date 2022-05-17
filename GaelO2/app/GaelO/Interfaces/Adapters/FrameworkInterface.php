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

    public static function getStoredFiles($directory = null);

    public static function storeFile(string $path, $contents) : void;

    public static function deleteFile(string $path) : void;

    public static function getFile(string $path) : string;

    public static function sendResetPasswordLink(string $email) : bool;

    public static function sendRegisteredEventForEmailVerification(int $userId) : void ;

    public static function createMagicLink(int $userId, string $redirectUrl): string;

}

<?php

namespace App\GaelO\Interfaces\Adapters;

Interface PhoneNumberInterface{
    public static function isValidPhoneNumber(string $phoneNumber) : bool;
}

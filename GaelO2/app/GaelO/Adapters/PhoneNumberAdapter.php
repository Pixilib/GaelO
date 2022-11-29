<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\PhoneNumberInterface;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberAdapter implements PhoneNumberInterface
{

    public static function isValidPhoneNumber(string $phoneNumber): bool
    {
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        return $phoneNumberUtil->isPossibleNumber($phoneNumber);
    }
}

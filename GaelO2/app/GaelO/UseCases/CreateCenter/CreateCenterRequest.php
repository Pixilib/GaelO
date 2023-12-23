<?php

namespace App\GaelO\UseCases\CreateCenter;

/**
 * @codeCoverageIgnore
 */
class CreateCenterRequest
{
    public int $currentUserId;
    public int $code;
    public String $name;
    public String $countryCode;
}

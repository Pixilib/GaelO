<?php

namespace App\GaelO\UseCases\CreateCenter;

class CreateCenterRequest {
    public unsignedInteger $code;
    public string $name;
    public string $country_code;
}

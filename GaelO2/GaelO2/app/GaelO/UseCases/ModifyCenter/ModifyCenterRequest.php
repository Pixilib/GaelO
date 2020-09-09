<?php

namespace App\GaelO\UseCases\ModifyCenter;

class ModifyCenterRequest {
    public int $currentUserId;
    public string $name;
    public int $code;
    public string $countryCode;
}

<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOUnauthorizedException extends GaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 401, "Unauthorized");
    }

}

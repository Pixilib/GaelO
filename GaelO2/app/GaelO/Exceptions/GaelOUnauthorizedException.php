<?php

namespace App\GaelO\Exceptions;

class GaelOUnauthorizedException extends AbstractGaelOException {

    public function __construct(string $message = "Unauthorized") {
        parent::__construct($message, 401, "Unauthorized");
    }

}

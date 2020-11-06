<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOBadRequestException extends GaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 400, "Bad Request");
    }

}

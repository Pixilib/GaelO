<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOValidateDicomException extends GaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 500, "Internal Server Error");
    }

}

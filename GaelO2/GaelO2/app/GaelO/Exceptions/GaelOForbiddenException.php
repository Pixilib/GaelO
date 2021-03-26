<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOForbiddenException extends GaelOException {

    public function __construct(string $message){
        parent::__construct($message, 403, "Forbidden");
    }
}

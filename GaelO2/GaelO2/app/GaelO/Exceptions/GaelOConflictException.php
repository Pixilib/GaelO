<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOConflictException extends GaelOException {

    public function __construct(string $message){
        parent::__construct($message, 409, "Conflict");
    }
}

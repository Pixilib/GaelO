<?php

namespace App\GaelO\Exceptions;

class GaelOConflictException extends AbstractGaelOException {

    public function __construct(string $message= "Conflict"){
        parent::__construct($message, 409, "Conflict");
    }
}

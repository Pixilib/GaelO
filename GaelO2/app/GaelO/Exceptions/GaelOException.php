<?php

namespace App\GaelO\Exceptions;

class GaelOException extends AbstractGaelOException {

    public function __construct(string $message = "Internal Server Error"){
        parent::__construct($message, 500, "Internal Server Error");
    }
}

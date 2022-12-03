<?php

namespace App\GaelO\Exceptions;

class GaelONotFoundException extends AbstractGaelOException {

    public function __construct(string $message = "Not Found"){
        parent::__construct($message, 404, "Not Found");
    }
}

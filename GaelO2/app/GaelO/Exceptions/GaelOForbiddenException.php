<?php

namespace App\GaelO\Exceptions;

class GaelOForbiddenException extends AbstractGaelOException {

    public function __construct( string $message = "" ){
        parent::__construct($message, 403, "Forbidden");
    }
}

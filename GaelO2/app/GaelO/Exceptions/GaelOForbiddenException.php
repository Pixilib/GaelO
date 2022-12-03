<?php

namespace App\GaelO\Exceptions;

class GaelOForbiddenException extends AbstractGaelOException {

    public function __construct( string $message = "Forbidden" ){
        parent::__construct($message, 403, "Forbidden");
    }
}

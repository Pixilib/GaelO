<?php

namespace App\GaelO\Exceptions;

class GaelOBadRequestException extends AbstractGaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 400, "Bad Request");
    }

}

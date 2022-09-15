<?php

namespace App\GaelO\Exceptions;

class GaelOValidateDicomException extends AbstractGaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 400, "Internal Server Error");
    }

}

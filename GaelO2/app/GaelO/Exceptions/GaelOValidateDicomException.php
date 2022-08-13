<?php

namespace App\GaelO\Exceptions;

class GaelOValidateDicomException extends AbstractGaelOException {

    public function __construct(string $message) {
        parent::__construct($message, 500, "Internal Server Error");
    }

}

<?php

namespace App\GaelO\Exceptions;

class GaelOValidateWsiException extends AbstractGaelOException {

    public function __construct(string $message = "Wsi validation exception") {
        parent::__construct($message, 400, "Bad Request");
    }

}

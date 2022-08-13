<?php

namespace App\GaelO\Exceptions;

class GaelONotFoundException extends AbstractGaelOException {

    public function __construct(string $message){
        parent::__construct($message, 404, "Not Found");
    }
}

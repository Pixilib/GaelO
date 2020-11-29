<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelONotFoundException extends GaelOException {

    public function __construct(string $message){
        parent::__construct($message, 404, "Not Found");
    }
}

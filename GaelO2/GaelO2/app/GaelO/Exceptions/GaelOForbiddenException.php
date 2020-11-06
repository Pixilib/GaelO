<?php

namespace App\GaelO\Exceptions;

use App\GaelO\Exceptions\GaelOException;

class GaelOForbiddenException extends GaelOException {

    public function __construct(){
        parent::__construct("", 403, "Forbidden");
    }
}

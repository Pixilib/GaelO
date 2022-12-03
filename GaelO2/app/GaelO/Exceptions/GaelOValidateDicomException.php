<?php

namespace App\GaelO\Exceptions;

class GaelOValidateDicomException extends AbstractGaelOException {

    public function __construct(string $message = "Dicom validation exception") {
        parent::__construct($message, 400, "Bad Request");
    }

}

<?php

namespace App\GaelO\Exceptions;

use Exception;

Abstract class GaelOException extends Exception {

    public string $statusCode;
    public string $statusText;

    public function __construct(string $message, int $statusCode, string $statusText){
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
    }

    public function getErrorBody(){
        return [
            'errorMessage'=> $this->getMessage()
        ];
    }


}

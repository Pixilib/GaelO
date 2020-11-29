<?php

namespace App\GaelO\UseCases\GetDicoms;

class GetDicomsResponse{
    public string $filename;
    public $body;
    public int $status;
    public string $statusText;
}

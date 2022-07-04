<?php

namespace App\GaelO\UseCases\GetDicomsFile;

class GetDicomsFileResponse
{
    public string $filename;
    public $body = null;
    public int $status;
    public string $statusText;
}

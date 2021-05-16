<?php

namespace App\GaelO\UseCases\GetFileOfForm;

class GetFileOfFormResponse{
    public $body = null;
    public ?string $filePath = null;
    public ?string $filename = null;
    public int $status;
    public string $statusText;
}

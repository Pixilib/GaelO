<?php

namespace App\GaelO\UseCases\GetFileOfVisit;

class GetFileOfVisitResponse
{
    public $body = null;
    public ?string $filePath = null;
    public ?string $filename = null;
    public int $status;
    public string $statusText;
}

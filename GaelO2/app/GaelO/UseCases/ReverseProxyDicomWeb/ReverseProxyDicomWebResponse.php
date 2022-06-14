<?php

namespace App\GaelO\UseCases\ReverseProxyDicomWeb;

class ReverseProxyDicomWebResponse
{
    public int $status;
    public string $statusText;
    public $body = null;
    public array $header;
}

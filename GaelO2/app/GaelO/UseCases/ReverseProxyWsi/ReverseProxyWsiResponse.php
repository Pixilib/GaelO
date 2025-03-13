<?php

namespace App\GaelO\UseCases\ReverseProxyWsi;

class ReverseProxyWsiResponse
{
    public int $status;
    public string $statusText;
    public $body = null;
    public array $header;
}

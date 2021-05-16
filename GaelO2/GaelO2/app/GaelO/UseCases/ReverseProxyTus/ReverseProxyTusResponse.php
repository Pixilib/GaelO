<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

class ReverseProxyTusResponse{
    public int $status;
    public string $statusText;
    public $body = null;
    public array $header;
}

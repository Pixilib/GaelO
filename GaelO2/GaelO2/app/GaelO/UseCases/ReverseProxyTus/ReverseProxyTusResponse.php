<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

class ReverseProxyTusResponse{
    public int $status;
    public string $statusText;
    public $body;
    public $response;
    public array $header;
}

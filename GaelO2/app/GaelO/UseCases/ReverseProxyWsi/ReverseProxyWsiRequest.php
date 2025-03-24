<?php

namespace App\GaelO\UseCases\ReverseProxyWsi;

class ReverseProxyWsiRequest
{
    public int $currentUserId;
    public string $url;
    public array $header;
    public $body;
}

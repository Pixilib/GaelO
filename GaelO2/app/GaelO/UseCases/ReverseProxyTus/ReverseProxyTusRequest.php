<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

class ReverseProxyTusRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public string $url;
    public string $method;
    public array $header;
    public $body;
}

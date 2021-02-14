<?php

namespace App\GaelO\UseCases\ReverseProxyDicomWeb;

class ReverseProxyDicomWebRequest{
    public int $currentUserId;
    public string $url;
    public array $header;
    public $body;
}

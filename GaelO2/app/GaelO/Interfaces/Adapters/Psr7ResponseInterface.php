<?php

namespace App\GaelO\Interfaces\Adapters;

use Psr\Http\Message\StreamInterface;

interface Psr7ResponseInterface
{
    public function getStatusCode(): int;
    public function getBody(): string;
    public function getStream(): StreamInterface;
    public function getReasonPhrase(): string;
    public function getHeaders(): array;
    public function getJsonBody(): array;
}

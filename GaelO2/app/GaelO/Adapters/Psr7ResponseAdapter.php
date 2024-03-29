<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Psr7ResponseAdapter implements Psr7ResponseInterface
{

    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getStream(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function getBody(): string
    {
        return $this->response->getBody()->getContents();
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getJsonBody(): array
    {
        $body = $this->response->getBody();
        return json_decode($body, true);
    }
}

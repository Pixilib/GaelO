<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use Psr\Http\Message\ResponseInterface;

class Psr7ResponseAdapter implements Psr7ResponseInterface
{


    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
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
        //SK : A Verifier pb de caractere accentue
        //$utf8_body = mb_convert_encoding($body, 'UTF-8');
        //https://github.com/guzzle/guzzle/issues/1664
        //Verif ce que retourne guzzle et checker format de la db (devrait etre utf8)
        //Checker si creation de la db bien faite en utf8
        return json_decode($body, true);
    }
}

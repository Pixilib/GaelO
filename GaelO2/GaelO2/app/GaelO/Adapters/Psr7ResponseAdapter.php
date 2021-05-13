<?php

namespace App\GaelO\Adapters;

use Psr\Http\Message\ResponseInterface;

class Psr7ResponseAdapter {


    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode() : int {
        return $this->response->getStatusCode();
    }

    public function getBody() : string {
        return $this->response->getBody()->getContents();
    }

    public function getReasonPhrase() :string {
        return $this->response->getReasonPhrase();
    }

    public function getHeaders()  : array {
        return $this->response->getHeaders();
    }

    public function getJsonBody() : array {
        $body = $this->response->getBody();
        return json_decode($body, true);
    }

}

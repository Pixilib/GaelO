<?php

namespace app\GaelO\Adapters;

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
        return $this->response->getBody();
    }

    public function getJsonBody() : array {
        $body = $this->response->getBody();
        return json_decode($body);
    }
}

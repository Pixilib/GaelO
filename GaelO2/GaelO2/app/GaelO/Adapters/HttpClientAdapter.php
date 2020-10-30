<?php

namespace App\GaelO\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class HttpClientAdapter {

    private string $login;
    private string $password;
    private string $address;
    private Client $client;

    public function __construct(){
        $this->client = new Client();
    }

    public function setAddress(string $address, int $port){
        $this->address = $address.':'.$port;
    }

    public function setBasicAuthentication(string $login, string $password){
        $this->login = $login;
        $this->password = $password;
    }

    public function request(string $method, string $uri, array $body = null) : Response{

        $request = new Request($method, $this->address.$uri, ['auth' => [$this->login, $this->password]] ,$body);
        return $this->client->send($request);

    }

    public function requestJson(string $method, string $uri, array $body = null) : array {
        $response = $this->request($method, $uri, $body);
        $body = $response->getBody();
        return json_decode( $body, true );
    }

}

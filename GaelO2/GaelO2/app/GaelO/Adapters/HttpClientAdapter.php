<?php

namespace App\GaelO\Adapters;

use GuzzleHttp\Client;

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

    public function request(string $method, string $uri, $body = null) : Psr7ResponseAdapter {

        $options = ['auth' => [$this->login, $this->password],
                    $body];

        $response = $this->client->request($method, $this->address.$uri , $options);
        return new Psr7ResponseAdapter($response);

    }

    public function requestJson(string $method, string $uri, array $body = []) : Psr7ResponseAdapter {
        $response = $this->request($method, $uri, ['json' => $body ]);
        return $response;
    }

}

<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class HttpClientAdapter implements HttpClientInterface
{

    private string $login = '';
    private string $password = '';
    private string $address;
    private string $authorizationToken='';
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function setAddress(string $address, int $port): void
    {
        $this->address = $address . ':' . $port;
    }

    public function setUrl(string $url): void
    {
        $this->address = $url;
    }

    public function setAuthorizationToken(string $authorizationToken): void
    {
        $this -> authorizationToken = $authorizationToken;
    }

    public function setBasicAuthentication(string $login, string $password): void
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function requestUploadArrayDicom(string $method, string $uri, array $files): array
    {

        $requests = function ($files) use ($method, $uri) {

            foreach ($files as $file) {
                $body = fopen($file, 'r');
                $headers = [
                    'Authorization' => "Basic " . base64_encode($this->login . ':' . $this->password),
                    'headers'  => ['content-type' => 'application/dicom', 'Accept' => 'application/json']
                ];

                yield new Request($method, $this->address . $uri, $headers, $body);
            }
        };

        $responseArray = [];

        $pool = new Pool($this->client, $requests($files), [
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $index) use (&$responseArray) {
                $responseArray[] = new Psr7ResponseAdapter($response);
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $responseArray;
    }

    public function requestJson(string $method, string $uri, array $body = []): Psr7ResponseInterface
    {
        $authenticationOption = ['auth' => [$this->login, $this->password]];
        $bodyOption = ['json' => $body];
        $options = array_merge($authenticationOption, $bodyOption);
        $response = $this->client->request($method, $this->address . $uri, $options);
        return new Psr7ResponseAdapter($response);
    }

    public function getResponseAsStream(string $method, string $uri, array $body = [])
    {

        $response = $this->client->request($method, $this->address . $uri, ['stream' => true, 'json' => $body, 'auth' => [$this->login, $this->password]]);

        if($response->getHeader('content-Length') != null) {
            $contentLength = $response->getHeader('content-Length')[0];
            header("Content-Length: " . $contentLength);
        }

        if($response->getHeader('content-Type') != null){
            $contentType = $response->getHeader('content-Type')[0];
            header("Content-Type: " . $contentType);

        }
        return $response->getBody();
    }

    public function streamResponse(string $method, string $uri, array $body = []): void
    {
        $body= $this->getResponseAsStream($method,$uri,$body);

        while (!$body->eof()) {
            echo $body->read(1024);
            flush();
        }
    }

    public function requestStreamResponseToFile(string $method, string $uri, $ressource, array $headers): Psr7ResponseInterface
    {
        $response = $this->client->request($method, $this->address . $uri, ['sink' => $ressource, 'auth' => [$this->login, $this->password], 'headers' => $headers]);
        return new Psr7ResponseAdapter($response);
    }

    public function rowRequest(string $method, string $uri, $body, ?array $headers ): Psr7ResponseInterface
    {
        $options = [];

        if ($body !== null) {
            $options['body'] = $body;
        }

        if ($this->login !== '' && $this->password !== '') {
            $options['auth'] = [$this->login, $this->password];
        }

        if ($headers != null) {
            $options['headers'] = $headers;
        }
        if ($this->authorizationToken != null) {
           $options['headers']['Authorization']='Bearer '.$this->authorizationToken;
        }


        $response = $this->client->request($method, $this->address . $uri, $options);
        return new Psr7ResponseAdapter($response);
    }

    public function requestUrlEncoded(string $uri ,array|string $payload): Psr7ResponseInterface  {
        $body = [
            'form_params' =>$payload ];

        $response=$this -> client -> request('POST',$uri,$body );
        return new Psr7ResponseAdapter($response)  ;
    }
}
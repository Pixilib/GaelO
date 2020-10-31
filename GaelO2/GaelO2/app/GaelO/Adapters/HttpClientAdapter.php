<?php

namespace App\GaelO\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
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

    public function request(string $method, string $uri, $body = null) : Psr7ResponseAdapter {
        $options = ['auth' => [$this->login, $this->password]];

        if($body !==null) {
            $bodyOption = ['body' => $body];
            $options = array_merge($options, $bodyOption);
        }
        $response = $this->client->request($method, $this->address.$uri , $options);

        return new Psr7ResponseAdapter($response);

    }

    public function requestUploadDicom(string $method, string $uri, $body) : Psr7ResponseAdapter {
        $options = ['auth' => [$this->login, $this->password],
                    'headers'  => ['content-type' => 'application/dicom', 'Accept' => 'application/json'],
                    'body' => $body];

        $response = $this->client->request($method, $this->address.$uri , $options);

        return new Psr7ResponseAdapter($response);

    }

    public function requestUploadArrayDicom(string $method, string $uri, array $files){

        $requests = function ($files) use ($method, $uri) {

            foreach ($files as $file) {
                $body = fopen($file, 'r');
                $headers = ['auth' => [$this->login, $this->password],
                    'headers'  => ['content-type' => 'application/dicom', 'Accept' => 'application/json']
                ];

                yield new Request($method, $this->address.$uri, $headers , $body);
            }
        };

        $responseArray=[];

        $pool = new Pool($this->client, $requests($files), [
            'concurrency' => 4,
            'fulfilled' => function (Response $response, $index) use (&$responseArray) {
                $responseArray[] = json_decode($response->getBody()->getContents(), true);
            },
            'rejected' => function (RequestException $reason, $index) {
                // this is delivered each failed request
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $responseArray;
    }

    public function requestJson(string $method, string $uri, array $body = []) : Psr7ResponseAdapter {
        $authenticationOption = ['auth' => [$this->login, $this->password]];
        $bodyOption = ['json' => $body];
        $options = array_merge($authenticationOption, $bodyOption);
        $response = $this->client->request($method, $this->address.$uri , $options);
        return new Psr7ResponseAdapter($response);
    }

}

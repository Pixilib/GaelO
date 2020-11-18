<?php

namespace App\GaelO\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use ZipArchive;

class HttpClientAdapter {

    private string $login = '';
    private string $password = '';
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

    public function request(string $method, string $uri, $body = null, ?array $headers=null) : Psr7ResponseAdapter {
        $options = ['auth' => [$this->login, $this->password]];

        if($body !==null) {
            $bodyOption = ['body' => $body];
            $options = array_merge($options, $bodyOption);
        }

        if($headers !==null) {
            $bodyOption = ['headers' => $headers];
            $headers = array_merge($options, $headers);
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

    //SK le multithreading gagne pas de temps sur le nmve mais permet de pas avoir une copie coté orthanc
    /*
    public function collectInstancesInZip(array $orthancSeriesObject){

        $instanceArray = [];

        array_map(function($seriesObject) use (&$instanceArray) {
            $instanceArray = [...$instanceArray, ...$seriesObject->seriesInstances];
        } , $orthancSeriesObject);

        $requests = function ($orthancInstanceIDs) {

            foreach ($orthancInstanceIDs as $orthancInstanceID) {

                $headers = ['auth' => [$this->login, $this->password],
                    'headers'  => ['Accept' => 'application/zip']
                ];

                yield new Request('GET', $this->address.'/instances/'.$orthancInstanceID.'/file', $headers);
            }
        };

        $zip=new ZipArchive;
        $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPORTHANC_');
        $zip->open($tempZip, ZipArchive::CREATE);

        $pool = new Pool($this->client, $requests($instanceArray), [
            'concurrency' => 2,
            'fulfilled' => function (Response $response, $index) use (&$zip) {
                $zip->addFromString($index.".dcm", $response->getBody() );
            },
            'rejected' => function (RequestException $reason, $index) {
                // this is delivered each failed request
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $zip;

    }
    */

    public function requestJson(string $method, string $uri, array $body = []) : Psr7ResponseAdapter {
        $authenticationOption = ['auth' => [$this->login, $this->password]];
        $bodyOption = ['json' => $body];
        $options = array_merge($authenticationOption, $bodyOption);
        $response = $this->client->request($method, $this->address.$uri , $options);
        return new Psr7ResponseAdapter($response);
    }

    public function streamResponse(string $method, string $uri, array $body = []){

        $response = $this->client->request($method, $this->address.$uri, ['stream' =>true, 'json' => $body, 'auth' => [$this->login, $this->password] ]);

        $contentLength = $response->getHeader('content-Length')[0];
        $contentType = $response->getHeader('content-Type')[0];

        header("Content-Length: ".$contentLength);
        header("Content-Type: ".$contentType);

        $body = $response->getBody();
        while (!$body->eof()) {
            echo $body->read(1024);
            ob_flush();
            flush();
        }

    }

    /**
     * PSR7 can be the return value of the Laravel controller to stream the full response
     * will be usefull for reverse proxy purposes
     */
    public function getPSR7Response(string $method, string $uri, array $body = []){
        $response = $this->client->request($method, $this->address.$uri, ['stream' =>true, 'json' => $body, 'auth' => [$this->login, $this->password] ]);
        return $response;
    }

    public function requestStreamResponseToFile(string $method, string $uri, $ressource, array $headers){
        $response = $this->client->request($method, $this->address.$uri, ['sink' => $ressource, 'auth' => [$this->login, $this->password], 'headers'=> $headers ]);
        return $response;
    }

    public function rowRequest(string $method, string $url, $body,  array $headers ){
        error_log(print_r($headers, true));
        $response = $this->client->request($method, $url, ['body'=> $body, 'headers' => $headers ]);
        return $response;
    }

}

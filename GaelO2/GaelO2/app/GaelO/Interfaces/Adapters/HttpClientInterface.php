<?php

namespace App\GaelO\Interfaces\Adapters;

use App\GaelO\Adapters\Psr7ResponseAdapter;

interface HttpClientInterface
{

    public function setAddress(string $address, int $port): void;

    public function setBasicAuthentication(string $login, string $password): void;

    public function rowRequest(string $method, string $uri, $body, ?array $headers): Psr7ResponseAdapter;

    /**
     * Return array of PSR7 response adapter of multiple request, used to sent multiple files to an endpoint
     */
    public function requestUploadArrayDicom(string $method, string $uri, array $files): array ;

    /**
     * When request body is a JSON payload
     */
    public function requestJson(string $method, string $uri, array $body = []): Psr7ResponseAdapter;

    /**
     * Output response in script execution stream
     */
    public function streamResponse(string $method, string $uri, array $body = []): void;

    /**
     * Store response in destination file
     */
    public function requestStreamResponseToFile(string $method, string $uri, $ressource, array $headers): Psr7ResponseAdapter;


}

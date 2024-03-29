<?php

namespace App\GaelO\Interfaces\Adapters;

interface HttpClientInterface
{

    public function setAddress(string $address, int $port): void;

    public function setUrl(string $url): void;

    public function setAuthorizationToken(string $authorizationToken): void;

    public function setBasicAuthentication(string $login, string $password): void;

    public function rawRequest(string $method, string $uri, $body, ?array $headers, $ressourceDestination = null, $httpErrors = true): Psr7ResponseInterface;

    public function uploadFile(string $method, string $uri, string $filename) : Psr7ResponseInterface;
    /**
     * Return array of PSR7 response adapter of multiple request, used to sent multiple files to an endpoint
     */
    public function requestUploadArrayDicom(string $method, string $uri, array $files): array ;

    /**
     * When request body is a JSON payload
     */
    public function requestJson(string $method, string $uri, array $body = []): Psr7ResponseInterface;

    /**
     * Output response in script execution stream
     */
    public function streamResponse(string $method, string $uri, array $body = []): void;

    /**
     * Return Http respond as ressource
     */
    public function getResponseAsStream(string $method, string $uri, array $body = []);

    /**
     * Store response in destination file
     */
    public function requestStreamResponseToFile(string $method, string $uri, $ressource, array $headers, array $body = []): Psr7ResponseInterface;

    public function requestUrlEncoded(string $uri ,array|string $payload):Psr7ResponseInterface;
}

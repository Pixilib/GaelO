<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class GaelOClientService
{
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function loadUrl()
    {
        //Set address of Orthanc server
        $url = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);
        if ($url) $this->httpClientInterface->setUrl($url);
    }

    public function setAuthorizationToken(string $token): void
    {
        $this->httpClientInterface->setAuthorizationToken($token);
    }

    public function login(string $email, $password): void
    {
        $payload = [
            'email' => $email,
            'password' => $password
        ];
        $answer = $this->httpClientInterface->requestJson("POST", '/api/login', $payload);
        $this->httpClientInterface->setAuthorizationToken($answer['access_token']);
    }

    public function createFileToVisit(string $studyName, int $visitId, string $key, string $contentType, ?string $extension, string $filePath)
    {
        $payload = [
            'extension' => $extension,
            'content' => base64_encode(file_get_contents($filePath))
        ];
        $this->httpClientInterface->rawRequest("POST", '/api/visits/' . $visitId . '/files/' . $key . '?studyName=' . $studyName . '&role=Supervisor', $payload, ['Content-Type' => $contentType]);
    }
}

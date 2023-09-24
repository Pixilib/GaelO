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

    public function login(string $login, $password): void
    {
        //Set address of Orthanc server
        $url = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);
        if($url) $this->httpClientInterface->setUrl($url);

        if($login && $password) {
            $payload = [
                'email' => '',
                'password' => ''
            ];
            $this->httpClientInterface->requestJson("POST", '/api/login', $payload );
            $this->httpClientInterface->setAuthorizationToken($login, $password);
        }
    }

    public function createFileToVisit(string $studyName, int $visitId, string $key, string $contentType, string $filePath)
    {
        $this->httpClientInterface->rawRequest("POST", '/api/visits/' . $visitId . '/files/'.$key.'?studyName='.$studyName.'&role=Supervisor', base64_encode(file_get_contents($filePath)), ['CONTENT_TYPE' => $contentType]);
    }

    
}

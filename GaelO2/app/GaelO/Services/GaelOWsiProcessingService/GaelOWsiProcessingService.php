<?php

namespace App\GaelO\Services\GaelOWsiProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class GaelOWsiProcessingService
{
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->setParams();
    }

    public function setParams(): void
    {
        //Set Time Limit at 1H as operation could be long
        set_time_limit(3600);
        //Set address of Processing Server
        $url = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_URL);
        $login = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_LOGIN);
        $password = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_PASSWORD);
        $this->httpClientInterface->setUrl($url);
        $this->httpClientInterface->setBasicAuthentication($login, $password);
    }

    public function getWelcomeGaeloWsiProcessing()
    {
        $request = $this->httpClientInterface->rawRequest("GET", "/", null, null);
        return $request->getBody();
    }
}
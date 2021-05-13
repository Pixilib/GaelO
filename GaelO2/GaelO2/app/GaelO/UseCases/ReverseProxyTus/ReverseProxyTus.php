<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class ReverseProxyTus
{

    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse)
    {
        //No particular RBAC, authentified users are allowed to send data (complicated to implement to get only users mathching the uploaded visit id)
        //SK on peut ajouter la study dans le header via uppy pour faire un checkrole

        //Get Headers from Request
        $headers  = $reverseProxyTusRequest->header;
        //Set server information to make TUS able to send the correct server location for client
        $headers['X-Forwarded-Proto'] = $this->frameworkInterface::getConfig(SettingsConstants::APP_PROTOCOL);
        $headers['X-Forwarded-Host'] = $this->frameworkInterface::getConfig(SettingsConstants::APP_DOMAIN) . ':' . FrameworkInterface::getConfig(SettingsConstants::APP_PORT);

        //Get TUS address
        $address = $this->frameworkInterface::getConfig(SettingsConstants::TUS_ADDRESS);
        $port = $this->frameworkInterface::getConfig(SettingsConstants::TUS_PORT);

        //Make query of TUS
        $this->httpClientInterface->setAddress($address, $port);
        $response = $this->httpClientInterface->rowRequest($reverseProxyTusRequest->method, $reverseProxyTusRequest->url, $reverseProxyTusRequest->body, $headers);

        //Output response
        $reverseProxyTusResponse->status = $response->getStatusCode();
        $reverseProxyTusResponse->statusText = $response->getReasonPhrase();
        $reverseProxyTusResponse->body = $response->getBody();
        $reverseProxyTusResponse->header = $response->getHeaders();
    }
}

<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class ReverseProxyTus
{

    private HttpClientInterface $httpClientInterface;

    public function __construct(HttpClientInterface $httpClientInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
    }

    public function execute(ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse)
    {
        //No particular RBAC, authentified users are allowed to send data (complicated to implement to get only users mathching the uploaded visit id)
        //SK on peut ajouter la study dans le header via uppy pour faire un checkrole

        //Get Headers from Request
        $headers  = $reverseProxyTusRequest->header;
        //Set server information to make TUS able to send the correct server location for client
        $headers['X-Forwarded-Proto'] = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PROTOCOL);
        $headers['X-Forwarded-Host'] = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_DOMAIN) . ':' . LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PORT);

        //Get TUS address
        $address = LaravelFunctionAdapter::getConfig(SettingsConstants::TUS_ADDRESS);
        $port = LaravelFunctionAdapter::getConfig(SettingsConstants::TUS_PORT);

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

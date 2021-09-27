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
        $url = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);
        $parsedUrl = parse_url($url);
        $gaelOProtocol = $parsedUrl['scheme'];
        $gaelOHost = $parsedUrl['host'];
        $gaelOPort = $parsedUrl['port'];

        $headers['X-Forwarded-Proto'] = $gaelOProtocol;
        $headers['X-Forwarded-Host'] = $gaelOPort ? $gaelOHost.':'.$gaelOPort : $gaelOHost;

        //Make query of TUS
        $this->httpClientInterface->setUrl( $this->frameworkInterface::getConfig(SettingsConstants::TUS_URL) );
        $response = $this->httpClientInterface->rowRequest($reverseProxyTusRequest->method, $reverseProxyTusRequest->url, $reverseProxyTusRequest->body, $headers);

        //Output response
        $reverseProxyTusResponse->status = $response->getStatusCode();
        $reverseProxyTusResponse->statusText = $response->getReasonPhrase();
        $reverseProxyTusResponse->body = $response->getBody();
        $reverseProxyTusResponse->header = $response->getHeaders();
    }
}

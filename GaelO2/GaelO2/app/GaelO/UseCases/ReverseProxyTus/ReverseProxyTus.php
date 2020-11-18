<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Services\AuthorizationService;

class ReverseProxyTus{

    public function __construct(AuthorizationService $authorizationService, HttpClientAdapter $httpClientAdapter )
    {
        $this->authorizationService = $authorizationService;
        $this->httpClientAdapter = $httpClientAdapter;
    }

    public function execute(ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse){
        //Authorization check que Investigateur dans la study ?
        $address = LaravelFunctionAdapter::getConfig(SettingsConstants::TUS_ADDRESS);
        $port = LaravelFunctionAdapter::getConfig(SettingsConstants::TUS_PORT);
        $headers  = $reverseProxyTusRequest->header;
        $headers['X-Forwarded-Proto'] = "http";
        $headers['X-Forwarded-Host'] = "localhost:3000";
        error_log($reverseProxyTusRequest->url);
        $response = $this->httpClientAdapter->rowRequest($reverseProxyTusRequest->method, $address.':'.$port.$reverseProxyTusRequest->url, $reverseProxyTusRequest->body ,$headers);
        $reverseProxyTusResponse->status = $response->getStatusCode();
        $reverseProxyTusResponse->statusText = $response->getReasonPhrase();
        $reverseProxyTusResponse->body = $response->getBody();
        $reverseProxyTusResponse->header = $response->getHeaders();
    }

}

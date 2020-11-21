<?php

namespace App\GaelO\UseCases\ReverseProxyDicomWeb;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Constants\SettingsConstants;

class ReverseProxyDicomWeb{

    public function __construct(HttpClientAdapter $httpClientAdapter)
    {
        $this->httpClientAdapter = $httpClientAdapter;
    }

    public function execute(ReverseProxyDicomWebRequest $reverseProxyDicomWebRequest, ReverseProxyDicomWebResponse $reverseProxyDicomWebResponse){

        //SKImplementer logique de DicomWebAccess dans Authorization (determiner visibilité fonction studyUID)


        //Connect to Orthanc Pacs
        $this->httpClientAdapter->setAddress(
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS),
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PORT)
        );
        $this->httpClientAdapter->setBasicAuthentication(
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN),
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD)
        );
        //Remove our GaelO Prefix to match the orthanc route
        $calledUrl = str_replace("/api/orthanc", "", $reverseProxyDicomWebRequest->url);

        $gaelOProtocol = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PROTOCOL);
        $gaelOUrl = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_DOMAIN);
        $gaelOPort = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PORT);
        $headers= $reverseProxyDicomWebRequest->header;
        $headers['Forwarded'] = ['by=localhost;for=localhost;host='.$gaelOUrl.':'.$gaelOPort.'/api/orthanc'.';proto='.$gaelOProtocol];

        $response = $this->httpClientAdapter->rowRequest('GET', $calledUrl, null ,$headers);

        //Output response
        $reverseProxyDicomWebResponse->status = $response->getStatusCode();
        $reverseProxyDicomWebResponse->statusText = $response->getReasonPhrase();
        $reverseProxyDicomWebResponse->body = $response->getBody();
        $reverseProxyDicomWebResponse->header = $response->getHeaders();

    }

    private function checkAuthorization(){

    }
}

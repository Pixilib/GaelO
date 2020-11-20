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
        //SK ROUTE NON EXPOSEE, CONTROLLER NON FAIT
        //SKImplementer logique de DicomWebAccess dans Authorization (determiner visibilité fonction studyUID)
        //Se connecter a Orthanc PACS
        $this->httpClientAdapter->setAddress(
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS),
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PORT)
        );
        $this->httpClientAdapter->setBasicAuthentication(
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN),
            LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD)
        );
        //SK URL ENVOYE A PARSER
        //$finalURI=str_replace("orthanc/", "", $_SERVER['REQUEST_URI']);
        $calledUrl = '';

        $gaelOProtocol = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PROTOCOL);
        $gaelOUrl = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_DOMAIN);
        $gaelOPort = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PORT);
        $headers= $reverseProxyDicomWebRequest->header;
        $headers['Forwarded'] = ['by=localhost;for=localhost;host='.$gaelOUrl.':'.$gaelOPort.'/orthanc'.';proto='.$gaelOProtocol];

        $response = $this->httpClientAdapter->rowRequest('GET', $calledUrl, null ,$headers);

        //Output response
        $reverseProxyDicomWebResponse->status = $response->getStatusCode();
        $reverseProxyDicomWebResponse->statusText = $response->getReasonPhrase();
        $reverseProxyDicomWebResponse->body = $response->getBody();
        $reverseProxyDicomWebResponse->header = $response->getHeaders();

        /*
        $url = getenv("HOST_URL");
		$port = getenv("HOST_PORT");
		$protocol = getenv("HOST_PROTOCOL");
        $request=$request->withHeader('Forwarded', 'by=localhost;for=localhost;host='.$url.':'.$port.'/orthanc'.';proto='.$protocol);
        */
    }

    private function checkAuthorization(){

    }
}

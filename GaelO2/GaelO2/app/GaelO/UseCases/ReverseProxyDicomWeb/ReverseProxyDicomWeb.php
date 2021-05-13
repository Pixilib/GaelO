<?php

namespace App\GaelO\UseCases\ReverseProxyDicomWeb;

use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Services\AuthorizationDicomWebService;
use Exception;
use Illuminate\Support\Facades\Log;

class ReverseProxyDicomWeb{

    private AuthorizationDicomWebService $authorizationService;
    private HttpClientInterface $httpClientInterface;

    public function __construct( AuthorizationDicomWebService $authorizationService,  HttpClientInterface $httpClientInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReverseProxyDicomWebRequest $reverseProxyDicomWebRequest, ReverseProxyDicomWebResponse $reverseProxyDicomWebResponse){

        try{

             //Remove our GaelO Prefix to match the orthanc route
            $calledUrl = str_replace("/api/orthanc", "", $reverseProxyDicomWebRequest->url);

            $this->checkAuthorization($reverseProxyDicomWebRequest->currentUserId, $calledUrl );

            //Connect to Orthanc Pacs
            $this->httpClientInterface->setAddress(
                LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS),
                LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PORT)
            );
            $this->httpClientInterface->setBasicAuthentication(
                LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN),
                LaravelFunctionAdapter::getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD)
            );


            $gaelOProtocol = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PROTOCOL);
            $gaelOUrl = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_DOMAIN);
            $gaelOPort = LaravelFunctionAdapter::getConfig(SettingsConstants::APP_PORT);
            $headers= $reverseProxyDicomWebRequest->header;
            $headers['Forwarded'] = ['by=localhost;for=localhost;host='.$gaelOUrl.':'.$gaelOPort.'/api/orthanc'.';proto='.$gaelOProtocol];

            Log::info($calledUrl);
            $response = $this->httpClientInterface->rowRequest('GET', $calledUrl, null ,$headers);

            //Output response
            $reverseProxyDicomWebResponse->status = $response->getStatusCode();
            $reverseProxyDicomWebResponse->statusText = $response->getReasonPhrase();
            $reverseProxyDicomWebResponse->body = $response->getBody();
            $reverseProxyDicomWebResponse->header = $response->getHeaders();

        } catch (GaelOException $e){

            $reverseProxyDicomWebResponse->status = $e->statusCode;
            $reverseProxyDicomWebResponse->statusText = $e->statusText;
            $reverseProxyDicomWebResponse->body = $e->getErrorBody();

        } catch (Exception $e){
            throw $e;
        }


    }

    private function checkAuthorization(int $currentUserId, string $requestedURI){

        if( ! str_starts_with ( $requestedURI , "/dicom-web/" ) ){
            throw new GaelOForbiddenException;
        }
        //Pb ORTHANC N A PAS LES MEME ROUTES
        /*
        $this->authorizationService->setUserIdAndRequestedUri($currentUserId, $requestedURI);
        if(!$this->authorizationService->isDicomAllowed() ){
            throw new GaelOForbiddenException();
        };
        */

    }
}

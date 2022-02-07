<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;


class TokenService
{
    private $date;
    private $expiresOn=0;
    public $token;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    private function createAccessTokenAzure(): string
    {
        $requestUrl = "https://login.microsoftonline.com/" . $this->$frameworkInterface::getConfig(SettingsConstants::AZURE_DIRECTORY_ID). "/oauth2/token";
        $payload = [
            "grant_type" => "client_credentials",
            "client_id" => $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID),
            "client_secret" => $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET),
            "resource" => $this->resource,
        ];
        $response = $this->httpClientInterface->requestUrlEncoded($requestUrl, $payload)->getJsonBody();
        
        $token = $response["access_token"];
        $expiresOn=$response["expires_on"];

    }

    public function getToken():string {

        $date=time();
        if($expireOn - $date > 0)
        {
         createAccessTokenAzure();
        }
        return $token;
    }

   



}
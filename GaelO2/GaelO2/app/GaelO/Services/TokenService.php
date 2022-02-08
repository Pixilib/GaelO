<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Illuminate\Support\Facades\Log;


class TokenService
{
    private $date;
    public $expiresOn=0;
    public $token;
    private $tenantID;
    private $resource = "https://management.azure.com/";

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->tenantID = $frameworkInterface::getConfig(SettingsConstants::AZURE_DIRECTORY_ID);
        $this->createAccessTokenAzure();
    
    }

    public function createAccessTokenAzure()
    {
        $requestUrl = "https://login.microsoftonline.com/" .$this->tenantID. "/oauth2/token";
        $payload = [
            "grant_type" => "client_credentials",
            "client_id" => $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID),
            "client_secret" => $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET),
            "resource" => $this->resource,
        ];
        $response = $this->httpClientInterface->requestUrlEncoded($requestUrl, $payload)->getJsonBody();
        
        $this->token= $response["access_token"];
        $this->expiresOn=$response["expires_on"];
        
    }
    
    public function getToken() {
        
        $date=time();
       if($this->expiresOn - $date < 0)
        {   
         $this->createAccessTokenAzure();
         
         return $this->token;
        }
        return $this->token;      
    }
}
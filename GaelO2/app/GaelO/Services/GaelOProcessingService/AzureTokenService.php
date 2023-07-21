<?php

namespace App\GaelO\Services\GaelOProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class AzureTokenService
{
    public int $expiresOn = 0;
    public string $token;
    private string $tenantID;
    private string $clientId;
    private string $clientSecret;
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    /**
     * Request for authorization token from Azure
     * https://edi.wang/post/2021/8/23/how-to-get-access-token-for-azure-rest-apis-in-net
     */
    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->tenantID = $frameworkInterface::getConfig(SettingsConstants::AZURE_DIRECTORY_ID);
        $this->clientId = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID);
        $this->clientSecret = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET);
    }

    private function createAccessTokenAzure()
    {
        $requestUrl = "https://login.microsoftonline.com/" . $this->tenantID . "/oauth2/token";
        $payload = [
            "grant_type" => "client_credentials",
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "resource" =>  "https://management.azure.com/"
        ];
        $response = $this->httpClientInterface->requestUrlEncoded($requestUrl, $payload)->getJsonBody();

        $this->token = $response["access_token"];
        $this->expiresOn = $response["expires_on"];
    }

    public function getToken()
    {
        $date = time();
        if ($this->expiresOn - $date < 0) {
            $this->createAccessTokenAzure();
        }
        return $this->token;
    }
}

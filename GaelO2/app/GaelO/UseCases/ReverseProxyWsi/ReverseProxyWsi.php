<?php

namespace App\GaelO\UseCases\ReverseProxyWsi;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationWsiService;
use Exception;

class ReverseProxyWsi
{
    private AuthorizationWsiService $authorizationWsiService;

    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ReverseProxyWsiRequest $reverseProxyWsiRequest, ReverseProxyWsiResponse $reverseProxyWsiResponse)
    {

        try {

            //Remove our GaelO Prefix to match the orthanc route
            $calledUrl = str_replace("/api/orthanc", "", $reverseProxyWsiRequest->url);

            $this->checkAuthorization($reverseProxyWsiRequest->currentUserId, $calledUrl);

            //Connect to Orthanc Pacs
            $this->httpClientInterface->setUrl(
                $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_URL)
            );
            $this->httpClientInterface->setBasicAuthentication(
                $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN),
                $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD)
            );


            $gaelOAppURL = $this->frameworkInterface::getConfig(SettingsConstants::APP_URL);
            $parsedUrl = parse_url($gaelOAppURL);
            $gaelOProtocol = $parsedUrl['scheme'];
            $gaelOUrl = $parsedUrl['host'];
            $gaelOPort = array_key_exists('port', $parsedUrl) ? $parsedUrl['port'] : null;


            $headers = $reverseProxyWsiRequest->header;
            if ($gaelOPort) {
                $forwardedRule = 'by=localhost;for=localhost;host=' . $gaelOUrl . ':' . $gaelOPort . '/api/orthanc' . ';proto=' . $gaelOProtocol;
            } else {
                $forwardedRule = 'by=localhost;for=localhost;host=' . $gaelOUrl . '/api/orthanc' . ';proto=' . $gaelOProtocol;
            }

            $headers['Forwarded'] = [$forwardedRule];
            unset($headers["authorization"]);
            unset($headers["content-length"]);
            unset($headers["content-type"]);
            $response = $this->httpClientInterface->rawRequest('GET', $calledUrl, null, $headers);

            $responseHeaders  = $response->getHeaders();

            //Output response
            $reverseProxyWsiResponse->status = $response->getStatusCode();
            $reverseProxyWsiResponse->statusText = $response->getReasonPhrase();
            $reverseProxyWsiResponse->body = $response->getBody();
            $reverseProxyWsiResponse->header = $responseHeaders;
        } catch (AbstractGaelOException $e) {
            $reverseProxyWsiResponse->status = $e->statusCode;
            $reverseProxyWsiResponse->statusText = $e->statusText;
            $reverseProxyWsiResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $requestedURI)
    {
       
    
    }
}

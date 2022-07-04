<?php

namespace App\GaelO\UseCases\ReverseProxyDicomWeb;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationDicomWebService;
use Exception;

class ReverseProxyDicomWeb
{

    private AuthorizationDicomWebService $authorizationDicomWebService;
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(AuthorizationDicomWebService $authorizationDicomWebService,  HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->authorizationDicomWebService = $authorizationDicomWebService;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ReverseProxyDicomWebRequest $reverseProxyDicomWebRequest, ReverseProxyDicomWebResponse $reverseProxyDicomWebResponse)
    {

        try {

            //Remove our GaelO Prefix to match the orthanc route
            $calledUrl = str_replace("/api/orthanc", "", $reverseProxyDicomWebRequest->url);

            $this->checkAuthorization($reverseProxyDicomWebRequest->currentUserId, $calledUrl);

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


            $headers = $reverseProxyDicomWebRequest->header;
            if ($gaelOPort) {
                $forwardedRule = 'by=localhost;for=localhost;host=' . $gaelOUrl . ':' . $gaelOPort . '/api/orthanc' . ';proto=' . $gaelOProtocol;
            } else {
                $forwardedRule = 'by=localhost;for=localhost;host=' . $gaelOUrl . '/api/orthanc' . ';proto=' . $gaelOProtocol;
            }

            $headers['Forwarded'] = [$forwardedRule];

            $response = $this->httpClientInterface->rowRequest('GET', $calledUrl, null, $headers);

            //Output response
            $reverseProxyDicomWebResponse->status = $response->getStatusCode();
            $reverseProxyDicomWebResponse->statusText = $response->getReasonPhrase();
            $reverseProxyDicomWebResponse->body = $response->getBody();
            $reverseProxyDicomWebResponse->header = $response->getHeaders();
        } catch (GaelOException $e) {
            $reverseProxyDicomWebResponse->status = $e->statusCode;
            $reverseProxyDicomWebResponse->statusText = $e->statusText;
            $reverseProxyDicomWebResponse->body = $e->getErrorBody();
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId, string $requestedURI)
    {
        if (!str_starts_with($requestedURI, "/dicom-web/")) {
            throw new GaelOForbiddenException;
        }

        $this->authorizationDicomWebService->setRequestedUri($requestedURI);
        $this->authorizationDicomWebService->setUserId($userId);
        if (!$this->authorizationDicomWebService->isDicomAllowed()) {
            throw new GaelOForbiddenException("No Access to Dicom Web ressource");
        }
    }
}

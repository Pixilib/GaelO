<?php

namespace App\GaelO\Services\GaelOProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use App\GaelO\Services\OrthancService;

class GaelOProcessingService
{

    private HttpClientInterface $httpClientInterface;
    private OrthancService $orthancService;
    private FrameworkInterface $frameworkInterface;

    private string $host;
    private int $port;
    private string $protocol;

    /**
     * GaelO Processing Interaction
     */
    public function __construct(OrthancService $orthancService, FrameworkInterface $frameworkInterface, HttpClientInterface $httpClientInterface)
    {

        $this->httpClientInterface = $httpClientInterface;
        $this->orthancService = $orthancService;
        $this->frameworkInterface = $frameworkInterface;
        //Set GAELO Processing URL Passed in Env variable (default address)
        $this->port = $this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_PORT);
        $this->protocol = $this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_PROTOCOL);
        $this->host = $this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_HOST);
        $this->setServerAdress();
        //Need to access to Orthanc storage
        $this->orthancService->setOrthancServer(true);
    }

    public function setHost(string $host)
    {
        $this->host = $host;
    }
    /**
     * Setter for dynamic IP of gaelo processing
     */
    public function setServerAdress()
    {
        $url = $this->protocol . $this->host . ':' . $this->port;
        $this->httpClientInterface->setUrl($url);
    }

    /**
     * Fetch zipped dicom and transmit it to GaelO Processing
     */
    public function sendDicom(array $orthancID, ?string $transferSyntaxUID): Psr7ResponseInterface
    {
        // Fetch dicom from Orthanc
        $psr7Response = $this->orthancService->getOrthancZipStreamAsString($orthancID, $transferSyntaxUID);
        // Send Dicom to GaelO Processing
        $response = $this->httpClientInterface->rowRequest('POST', "/app/dicom", $psr7Response->getBody(), ['content-type' => 'application/zip', 'Accept' => 'application/json']);

        return $response;
    }
}

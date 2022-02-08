<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use App\GaelO\Services\OrthancService;

class GaelOProcessingService{

    private HttpClientInterface $httpClientInterface;
    private OrthancService $orthancService;
    private FrameworkInterface $frameworkInterface;

    public function __construct( OrthancService $orthancService, FrameworkInterface $frameworkInterface, HttpClientInterface $httpClientInterface ){

        $this->httpClientInterface=$httpClientInterface;
        $this->orthancService=$orthancService;
        $this->frameworkInterface=$frameworkInterface;
        //Set GAELO Processing URL Passed in Env variable (default address)
        $url = $this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_PROTOCOL).$this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_HOST).$this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_PORT);
        $this->httpClientInterface->setUrl($url);
        //Need to access to Orthanc storage
        $this->orthancService->setOrthancServer(true);
    }

    /**
     * Setter for dynamic IP of gaelo processing
     */
    public function setServerAdress(string $address){
        $this->httpClientInterface->setUrl($address);
    }

    /**
     * Fetch zipped dicom and transmit it to GaelO Processing
     */
    public function sendDicom (array $orthancID):Psr7ResponseInterface{
        // recupere la dicom
        $psr7Response = $this ->orthancService ->getOrthancZipStreamAsString($orthancID);
        // envoie la dicom
        $response = $this->httpClientInterface->rowRequest ('POST' ,"/app/dicom", $psr7Response->getBody(), ['content-type' => 'application/zip', 'Accept' => 'application/json']);

        return $response;
    }
}

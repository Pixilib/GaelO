<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

/**
 * Service class to retrieve and remove upload ZIP from Tus microservice
 */
class TusService{

    private HttpClientInterface $httpClientInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface){
        $this->httpClientInterface = $httpClientInterface;
        $url = $frameworkInterface::getConfig(SettingsConstants::TUS_URL);
        $this->httpClientInterface->setUrl($url);
    }

    public function getZip(string $tusFileId) : string {

        $downloadedFileName = tempnam(sys_get_temp_dir(), 'TusDicom');

        $resource  = fopen( $downloadedFileName, 'r+');

        $this->httpClientInterface->requestStreamResponseToFile('GET', '/api/tus/'.$tusFileId,  $resource, ['Tus-Resumable' => '1.0.0'] );

        return $downloadedFileName;

    }

    public function deleteZip(string $tusFileId) : void {
        $this->httpClientInterface->rowRequest('DELETE', '/api/tus/'.$tusFileId, null, ['Tus-Resumable' => '1.0.0'] );
    }

}

<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;

/**
 * Service class to retrieve and remove upload ZIP from Tus microservice
 */
class TusService{


    public function __construct(HttpClientAdapter $httpClientAdapter, LaravelFunctionAdapter $laravelFunctionAdapter){
        $this->httpClientAdapter = $httpClientAdapter;
        $this->laravelFunctionAdapter = $laravelFunctionAdapter;

        $address = $this->laravelFunctionAdapter->getConfig(SettingsConstants::TUS_ADDRESS);
        $port = $this->laravelFunctionAdapter->getConfig(SettingsConstants::TUS_PORT);
        $this->httpClientAdapter->setAddress($address, $port);
    }

    public function getZip(string $tusFileId) : string {

        $downloadedFileName = tempnam(sys_get_temp_dir(), 'TusDicom');

        $resource  = fopen( $downloadedFileName, 'r+');

        $this->httpClientAdapter->requestStreamResponseToFile('GET', '/tus/'.$tusFileId,  $resource, ['Tus-Resumable' => '1.0.0'] );

        return $downloadedFileName;

    }

    public function deleteZip(string $tusFileId) : void {
        $this->httpClientAdapter->request('DELETE', '/tus/'.$tusFileId, null, ['Tus-Resumable' => '1.0.0'] );
    }

}

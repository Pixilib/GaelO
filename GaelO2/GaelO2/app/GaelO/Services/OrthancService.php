<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Constants\SettingsConstants;

class OrthancService {

    public function __construct(HttpClientAdapter $httpClientAdapter, LaravelFunctionAdapter $laravelFunctionAdapter) {
        $this->httpClientAdapter = $httpClientAdapter;
        $this->laravelFunctionAdapter = $laravelFunctionAdapter;
    }

    public function setOrthancServer(bool $storage){
        //Set Time Limit at 3H as operation could be really long
		set_time_limit(10800);
		//Set address of Orthanc server
		if ($storage) {
			$address = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS);
            $port = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_PORT);
            $login = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN);
            $password = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD);
		}else {
            $address = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_ADDRESS);
            $port = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_PORT);
            $login = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_LOGIN);
            $password = $this->laravelFunctionAdapter->getConfig(SettingsConstants::ORTHANC_TEMPORARY_PASSWORD);

       }

       $this->httpClientAdapter->setAddress($address, $port);
       $this->httpClientAdapter->setBasicAuthentication($login, $password);

    }

    public function getOrthancPeers(){
        return $this->httpClientAdapter->requestJson('GET', '/peers');
    }

}

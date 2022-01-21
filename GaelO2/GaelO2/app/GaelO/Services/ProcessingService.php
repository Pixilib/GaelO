<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use App\GaelO\Services\OrthancService;
use Illuminate\Support\Facades\Log;

class ProcessingService{

    // variable
    private HttpClientInterface $httpClientInterface;
    private OrthancService $orthancService;
    private FrameworkInterface $frameworkInterface;


    // constructor

    public function __construct( OrthancService $orthancService, FrameworkInterface $frameworkInterface, HttpClientInterface $httpClientInterface )
    {
        $this->httpClientInterface=$httpClientInterface;
        $this->orthancService=$orthancService;
        $this->frameworkInterface=$frameworkInterface;
        $this->setServerAddress();
        $this->orthancService->setOrthancServer(true);
    }

    // fonction
    private function setServerAddress(){
        $url = $this->frameworkInterface::getConfig(SettingsConstants::GAELO_PROCESSING_URL);
        $this->httpClientInterface->setUrl($url);
    }

    /*
    *Recupere la dicom dans orthancPACS pour l'envoyer dans GaelOProcessing
    */
    public function sendDicom (array $orthancID):Psr7ResponseInterface{
   
        // recupere la dicom
         $stream = $this ->orthancService ->getOrthancZipStream2($orthancID);
        // envoie la dicom$
        
         $response = $this->httpClientInterface->rowRequest ('POST' ,"/app/dicom", $stream,['content-type' => 'application/zip', 'Accept' => 'application/json']);
         return $response;
         
     }
 } 
<?php

namespace App\GaelO\Services;

use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

class GaelOProcessingService
{

    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->setParams();
    }

    public function setParams(): void
    {
        //Set Time Limit at 1H as operation could be long
        set_time_limit(3600);
        //Set address of Processing Server
        $url = "http://172.17.0.1:8001";
        if ($url) $this->httpClientInterface->setUrl($url);
    }

    public function createSeriesFromOrthanc(string $orthancSeriesId, bool $pet = false, bool $convertToSuv = false)
    {
        $request = $this->httpClientInterface->requestJson('POST', "/tools/create-series-from-orthanc", ['seriesId' => $orthancSeriesId, 'PET'=> $pet, 'convertToSuv'=>$convertToSuv]);
        return $request->getBody();
    }

    public function executeInference(string $modelName, array $payload)
    {
        $request = $this->httpClientInterface->requestJson('POST', "/models/" . $modelName . "/inference", $payload);
        return $request->getJsonBody();
    }

    public function createMIPForSeries(string $seriesId, ?string $maskId = null): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('POST', "/series/" . $seriesId . "/mip", $downloadedFilePath, ['content-Type' => 'application/json'], ['maskId' => $maskId]);
        return $downloadedFilePath;
    }
}

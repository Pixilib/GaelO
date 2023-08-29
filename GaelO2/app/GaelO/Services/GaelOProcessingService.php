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
        $request = $this->httpClientInterface->requestJson('POST', "/tools/create-series-from-orthanc", ['seriesId' => $orthancSeriesId, 'PET' => $pet, 'convertToSuv' => $convertToSuv]);
        return $request->getBody();
    }

    public function executeInference(string $modelName, array $payload)
    {
        $request = $this->httpClientInterface->requestJson('POST', "/models/" . $modelName . "/inference", $payload);
        return $request->getJsonBody();
    }

    public function createMIPForSeries(string $seriesId, array $payload = []): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('POST', "/series/" . $seriesId . "/mip", $downloadedFilePath, ['content-Type' => 'application/json'], $payload);
        return $downloadedFilePath;
    }

    public function getNiftiMask(string $maskId): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('GET', "/masks/" . $maskId . "/file", $downloadedFilePath, ['content-Type' => 'application/json'], []);
        return $downloadedFilePath;
    }

    public function getNiftiSeries(string $imageId): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('GET', "/series/" . $imageId . "/file", $downloadedFilePath, ['content-Type' => 'application/json'], []);
        return $downloadedFilePath;
    }

    public function createRtssFromMask(string $orthancSeriesId, string $maskId): string
    {
        $payload = [
            'maskId' => $maskId,
            'orthancSeriesId' => $orthancSeriesId
        ];

        $request = $this->httpClientInterface->requestJson('POST', "/tools/mask-to-rtss", $payload);
        return $request->getBody();
    }

    public function getRtss(string $rtssId): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('GET', "/rtss/" . $rtssId . "/file", $downloadedFilePath, []);
        return $downloadedFilePath;
    }

    public function createSegFromMask(string $orthancSeriesId, string $maskId): string
    {
        $payload = [
            'maskId' => $maskId,
            'orthancSeriesId' => $orthancSeriesId
        ];

        $request = $this->httpClientInterface->requestJson('POST', "/tools/mask-to-seg", $payload);
        return $request->getBody();
    }

    public function getSeg(string $segId): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $this->httpClientInterface->requestStreamResponseToFile('GET', "/seg/" . $segId . "/file", $downloadedFilePath, []);
        return $downloadedFilePath;
    }

    public function fragmentMask(string $seriesId, string $maskId): string
    {
        $payload = [
            'maskId' => $maskId,
            'seriesId' => $seriesId
        ];

        $request = $this->httpClientInterface->requestJson('POST', "/tools/mask-fragmentation", $payload);
        return $request->getBody();
    }
}

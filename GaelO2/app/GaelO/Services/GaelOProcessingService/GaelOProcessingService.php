<?php

namespace App\GaelO\Services\GaelOProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Illuminate\Support\Facades\Log;

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
        $url = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_PROCESSING_URL);
        $login = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_PROCESSING_LOGIN);
        $password = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_PROCESSING_PASSWORD);
        $this->httpClientInterface->setUrl($url);
        $this->httpClientInterface->setBasicAuthentication($login, $password);
    }

    public function createSeriesFromOrthanc(string $orthancSeriesId, bool $pet = false, bool $convertToSuv = false)
    {
        $request = $this->httpClientInterface->requestJson('POST', "/tools/create-series-from-orthanc", ['seriesId' => $orthancSeriesId, 'PET' => $pet, 'convertToSuv' => $convertToSuv]);
        return $request->getBody();
    }

    public function createDicom(string $filename){
        $request = $this->httpClientInterface->uploadFile('POST', "/dicoms", $filename);
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

    public function thresholdMask(string $maskId, string $seriesId, string|float $threshold)
    {
        $payload = [
            'maskId' => $maskId,
            'seriesId' => $seriesId,
            'threshold' => $threshold
        ];
        $request = $this->httpClientInterface->requestJson('POST', "/tools/threshold-mask", $payload);
        return $request->getBody();
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

    public function getMaskDicomOrientation(string $maskId, string $orientation,  bool $compress): string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_Inference_');

        $payload = [
            'maskId' => $maskId,
            'orientation' => $orientation,
            'compress' => $compress
        ];

        $this->httpClientInterface->requestStreamResponseToFile('POST', "/tools/mask-dicom", $downloadedFilePath, [], $payload);
        return $downloadedFilePath;
    }

    public function getStatsMask(string $maskId): array
    {
        $request = $this->httpClientInterface->requestJson('GET', "/masks/" . $maskId . "/stats");
        return $request->getJsonBody();
    }

    public function deleteRessource(string $type, string $id): void
    {
        $request = $this->httpClientInterface->requestJson('DELETE', "/" . $type . "/" . $id);
    }
}

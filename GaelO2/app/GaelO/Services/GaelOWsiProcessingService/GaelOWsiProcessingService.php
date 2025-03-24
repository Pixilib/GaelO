<?php

namespace App\GaelO\Services\GaelOWsiProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Throwable;

class GaelOWsiProcessingService
{
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
        $this->setParams();
    }

    private function setParams(): void
    {
        //Set Time Limit at 1H as operation could be long
        set_time_limit(3600);
        //Set address of Processing Server
        $url = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_URL);
        $login = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_LOGIN);
        $password = $this->frameworkInterface->getConfig(SettingsConstants::GAELO_WSI_PROCESSING_PASSWORD);
        $this->httpClientInterface->setUrl($url);
        $this->httpClientInterface->setBasicAuthentication($login, $password);
    }

    public function ping(): bool
    {
        try {
            $this->httpClientInterface->rawRequest("GET", "/", null, null);
            return true;
        } catch (Throwable $e) {
        }
        return false;
    }

    public function postWsiImage(string $filename)
    {
        $request = $this->httpClientInterface->uploadFile('POST', '/wsi/', $filename);
        return $request->getJsonBody()['id'];
    }

    public function getWsiImage(string $wsiId): bool
    {
        try {
            $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_WSI_');
            $this->httpClientInterface->requestStreamResponseToFile('GET', "/wsi/" . $wsiId, $downloadedFilePath, ['Content-Type' => 'application/zip']);
            return true;
        } catch (Throwable $e) {
        }
        return false;
        
    }

    public function getDicom(string $studyInstanceUID)
    {
        try {
            $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_WSI_');
            $this->httpClientInterface->requestStreamResponseToFile('GET', "/dicom/" . $studyInstanceUID, $downloadedFilePath, ['Content-Type' => 'application/zip']);
            return $downloadedFilePath;
        } catch (Throwable $e) {}
        return null;
        
    }
    public function convertToDicom(array $wsiData, string $patientID, string $patientName, string $studyDescription, string $accessionNumber, ?string $studyId = null, ?string $manufacturer = null, ?string $imageType = null)
    { 
        $slides = [];
        foreach ($wsiData as $wsi) {
            $slides[] = [
                "dicom_tags_series" => [
                    "SeriesDescription" => $wsi['SeriesDescription'] ?? "",
                    "SeriesNumber" => $wsi['SeriesNumber'] ?? "1",
                ],
                "wsi_id" => $wsi['wsi_id']
            ];
        }

        $payload = [
            'dicom_tags_study' => [
                'PatientID' => $patientID,
                'PatientName' => $patientName,
                'StudyDescription' => $studyDescription,
                'StudyID' => $studyId,
                'AccessionNumber' => $accessionNumber,
                'Manufacturer' => $manufacturer,
                'FocusMethod' => 'AUTO',
                'ExtendedDepthOfField' => 'NO',
                'ImageType' => $imageType,
                'SpecimenDescriptionSequence' => [[
                    "SpecimenIdentifier" => "Specimen^Identifier",
                    "SpecimenUID" => "1.2.276.0.7230010.3.1.4.3252829876.4112.1426166133.871",
                    "IssuerOfTheSpecimenIdentifierSequence" => [],
                    "SpecimenPreparationSequence" => []
                ]]
            ],
            'slides' => $slides

        ];


        $request =  $this->httpClientInterface->requestJson('POST', "/tools/conversion/", $payload);
        return $request->getJsonBody();
    }

    public function getWsiMetadata(string $wsiId)
    {
        try {
            $this->httpClientInterface->rawRequest('GET', "/wsi/" . $wsiId . "/metadata", null, null);
            return true;
        } catch (Throwable $e) {
        }
        return false;
    }


    public function deleteDicom(string $studyInstanceUID)
    {
        try {
            $this->httpClientInterface->rawRequest('DELETE', "/dicom/" . $studyInstanceUID, null, null);
            return true;
        } catch (Throwable $e) {

        }
        return false;
    }

    public function deleteWsi(string $wsiId)
    {
        try {
            $this->httpClientInterface->rawRequest('DELETE', "/wsi/" . $wsiId, null, null);
            return true;
        } catch (Throwable $e) {

        }
        return false;
    }
}

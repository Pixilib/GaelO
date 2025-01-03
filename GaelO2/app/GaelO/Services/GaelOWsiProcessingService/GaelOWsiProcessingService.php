<?php

namespace App\GaelO\Services\GaelOWsiProcessingService;

use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;

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

    public function setParams(): void
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

    public function getWelcomeGaeloWsiProcessing()
    {
        $request = $this->httpClientInterface->rawRequest("GET", "/", null, null);
        return $request;
    }

    public function postWsiImage(string $filename)
    {
        $request = $this->httpClientInterface->uploadFile('POST', '/wsi/', $filename);
        return $request;
    }

    public function getWsiImage(string $wsiId)
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_WSI_');
        return $this->httpClientInterface->requestStreamResponseToFile('GET', "/wsi/" . $wsiId, $downloadedFilePath, ['Content-Type' => 'application/zip']);
    }

    public function getDicom(string $dicomId)
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_DICOM_');
        return $this->httpClientInterface->requestStreamResponseToFile('GET', "/dicom/" . $dicomId, $downloadedFilePath, ['Content-Type' => 'application/zip']);
    }

    public function convertToDicom(string $patientID, string $patientName, string $studyDescription, string $studyID, string $accessionNumber, array $wsiData,  string $manufacturer = null, string $imageType = null)
    {
        foreach ($wsiData as $wsi) {
            if (!isset($wsi['SeriesDescription']) || !isset($wsi['SeriesNumber'])) {
                throw new \InvalidArgumentException('Missing SeriesDescription or SeriesNumber in WSI data');
            }
        
            $slides[] = [
                "dicom_tags_series" => [
                    "SeriesDescription" => $wsi['SeriesDescription'],
                    "SeriesNumber" => $wsi['SeriesNumber'],
                ],
                "wsi_id" => $wsi['id']
            ];
        }

        $payload = [
            'dicoms_tags_study' => [
                'PatientID' => $patientID,
                'PatientName' => $patientName,
                'StudyDescription' => $studyDescription,
                'StudyID' => $studyID,
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
            return $request->getBody();
    }
}
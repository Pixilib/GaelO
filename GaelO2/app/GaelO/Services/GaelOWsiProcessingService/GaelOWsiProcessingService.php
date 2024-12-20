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
        $request = $this->httpClientInterface->uploadFile('POST', '/wsi', $filename);
        return $request->getBody();
    }

    public function getWsiImage(string $wsiId) : string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_WSI_');
        $this->httpClientInterface->requestStreamResponseToFile('GET', "/wsi/" . $wsiId, $downloadedFilePath, ['Content-Type' => 'application/zip'] );
    }

    public function getDicom(string $dicomId) : string
    {
        $downloadedFilePath  = tempnam(ini_get('upload_tmp_dir'), 'TMP_DICOM_');
        $this->httpClientInterface->requestStreamResponseToFile('GET', "/dicom/" . $dicomId, $downloadedFilePath, ['Content-Type' => 'application/zip'] );
    }

    public function convertToDicom(string $PatientID, string $PatientName, string $StudyDescription, string $StudyID, string $SeriesNumber, string $AccessionNumber, string $Manufacturer, string $ImageType, array $SeriesDecription, array $wsiId)
    {
        // Construire les slides
        $slides = [];
        foreach ($SeriesDescriptions as $index => $description) {
            $slides[] = [
                "dicom_tags_series" => [
                    "SeriesDescription" => $description
                ],
                "wsi_id" => $wsiIds[$index]
            ];
        }

        $payload = [
            'dicoms_tags_study' => [ 
                'PatientID' => $PatientID,
            'PatientName' => $PatientName,
            'StudyDescription' => $StudyDescription,
            'StudyID' => $StudyID,
            'SeriesNumber' => $SeriesNumber,
            'AccessionNumber' => $AccessionNumber,
            'Manufacturer' => $Manufacturer,
            'FocusMethod' => 'AUTO',
            'ExtendedDepthOfField' => 'NO',
            'ImageType' => $ImageType,
            'SpecimenDescriptionSequence' => [
                "SpecimenIdentifier" => "Specimen^Identifier",
                "SpecimenUID" => "1.2.276.0.7230010.3.1.4.3252829876.4112.1426166133.871",
                "IssuerOfTheSpecimenIdentifierSequence" => [],
                "SpecimenPreparationSequence" => []
                ]
            ],
            'slides' => $slides
            
        ];
        $request = $this->httpClientInterface->requestJson('POST', "/tools/conversion", $payload);
        return $request->getBody();
    }
}
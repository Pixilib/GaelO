<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Services\GaelOWsiProcessingService\GaelOWsiProcessingService;
use Tests\TestCase;
use Illuminate\Support\Facades\App;


class GaelOWsiProcessingServiceTest extends TestCase
{
    private GaelOWsiProcessingService $gaeloWsiProcessingService;

    protected function setUp(): void
    {

        parent::setUp();
        $this->gaeloWsiProcessingService = App::make(GaelOWsiProcessingService::class);
        $this->markTestSkipped()
    }
    
    public function testWelcome()
    {
        $resultat = $this->gaeloWsiProcessingService->getWelcomeGaeloWsiProcessing();
        $this->assertEquals(200, $resultat->getStatusCode());
        $this->assertEquals('Welcome to GaelO Pathology Processing Backend !', $resultat->getBody());
    }


    public function  testPostWsiImage()
    {
        $path = getcwd() . "/tests/data/images_wsi.zip";
        $resultat = $this->gaeloWsiProcessingService->postWsiImage($path);
        $this->assertEquals(200, $resultat->getStatusCode());
    }


    public function testGetWsiImage()
    {
        $resultat = $this->gaeloWsiProcessingService->getWsiImage('a38c8a8f747e3858c615614e4e0f6d30');
        $this->assertEquals(200, $resultat->getStatusCode());
    }

    public function testGetDicom()
    {
        $resultat = $this->gaeloWsiProcessingService->getDicom('a38c8a8f747e3858c615614e4e0f6d30');
        $this->assertEquals(200, $resultat->getStatusCode());
    }

    public function  testConvertToDicom()
    {

        $patientID = "12345";
        $patientName = "John Doe";
        $studyDescription = "Cancer Study";
        $studyID = "67890";
        $accessionNumber = "ACC12345";
        $wsiData = [
            [
                "SeriesDescription" => "Series 1",
                "SeriesNumber" => '1',
                "id" => "a38c8a8f747e3858c615614e4e0f6d30"
            ],
            [
                "SeriesDescription" => "Series 2",
                "SeriesNumber" => '2',
                "id" => "b3a10b48bd26c96df930e7b2ecf0a9a4"
            ]
        ];
        $manufacturer = "manufcaturer";
        $imageType = "ORIGINAL\\SECONDARY";

        $resultat = $this->gaeloWsiProcessingService->convertToDicom(
            $patientID,
            $patientName,
            $studyDescription,
            $studyID,
            $accessionNumber,
            $wsiData,
            $manufacturer,
            $imageType
        );


        $this->assertEquals(200, $resultat->getStatusCode());
    }
}

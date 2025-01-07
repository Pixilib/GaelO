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
        //$this->markTestSkipped();
    }

    public function testWelcomeApi()
    {
        $result = $this->gaeloWsiProcessingService->ping();
        $this->assertTrue($result);
    }


    public function testPostWsiImage()
    {
        $path = getcwd() . "/tests/data/wsi-sample";
        $result = $this->gaeloWsiProcessingService->postWsiImage($path);
        $this->assertNotNull($result['id']);
        return $result['id'];
    }


    /**
     * @depends testPostWsiImage
     */
    public function testGetWsiImage($wsiId)
    {
        $result = $this->gaeloWsiProcessingService->getWsiImage($wsiId);
        $this->assertTrue($result);
    }

    public function testConvertToDicom()
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

        $result = $this->gaeloWsiProcessingService->convertToDicom(
            $wsiData,
            $patientID,
            $patientName,
            $studyDescription,
            $accessionNumber,
            $studyID,
            $manufacturer,
            $imageType
        );


        $this->assertIsArray($result);
        return $result['study_instance_uid'];
    }

    /**
     * @depends testConvertToDicom
     */
    public function testGetDicom($studyInstanceUID)
    {
        $result = $this->gaeloWsiProcessingService->getDicom($studyInstanceUID);
        $this->assertTrue($result);
    }

    /**
     * @depends testPostWsiImage
     */
    public function testGetWsiMetadata($wsiId)
    {
        $result = $this->gaeloWsiProcessingService->getWsiMetadata($wsiId);
        $this->assertTrue($result);
    }
}

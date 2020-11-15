<?php

namespace Tests\Feature;

use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Adapters\Psr7ResponseAdapter;
use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class OrthancServiceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientSpy  = $this->spy(HttpClientAdapter::class);

        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }

    }

    public function testGetPeers()
    {
        $this->httpClientSpy->shouldReceive('requestJson')->andReturns(
            new Psr7ResponseAdapter(new Response(200, [], json_encode(['test' => ['localhost', '8042']])))
        );
        $this->orthancService->getOrthancPeers();
        $this->httpClientSpy->shouldHaveReceived('requestJson')->with('GET', '/peers')->once();
    }

    public function testOrthancAddPeers()
    {
        $this->httpClientSpy->shouldReceive('requestJson')->andReturns(
            new Psr7ResponseAdapter(new Response(200, [], null))
        );
        $this->orthancService->addPeer('gaelotest', 'http://kanoun.fr:8043', 'salim', 'salim');
        $data = array(
            'Username' => 'salim',
            'Password' => 'salim',
            'Url' => 'http://kanoun.fr:8043'
        );
        $this->httpClientSpy->shouldHaveReceived('requestJson')->with('PUT', '/peers/gaelotest',  $data)->once();
    }

    public function testOrthancDeletePeers()
    {
        $this->httpClientSpy->shouldReceive('request')->andReturns(
            new Psr7ResponseAdapter(new Response(200, [], null))
        );
        $this->orthancService->deletePeer('gaelotest');
        $this->httpClientSpy->shouldHaveReceived('request')->with('DELETE', '/peers/gaelotest')->once();
    }

    public function testOrthancRemoveAllPeers()
    {
        $this->httpClientSpy->shouldReceive('request')->andReturns(
            new Psr7ResponseAdapter(new Response(200, [], null))
        );
        $this->orthancService->removeAllPeers();
        $this->httpClientSpy->shouldHaveReceived('request')->once();
    }

    public function testOrthancPeerIsTransferAccelerated()
    {
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $bool = $this->orthancService->isPeerAccelerated('gaelotest');
    }

    public function testOrthancSendToPeer()
    {
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $response = $this->orthancService->sendToPeer('gaelotest', [''], true);
    }

    public function testGetOrthancJobDetails()
    {
        $this->markTestSkipped('SK TO DO');
        $this->orthancService->getJobDetails('id');
    }

    public function testSendDicomFileArray()
    {
        $array = [
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm",
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0b7033a437f446e28a999d79ca9901ef.dcm"
        ];
        $answer = $this->orthancService->importFiles($array);
        $this->assertEquals(2, sizeof($answer));
    }

    public function testSendDicomFile()
    {
        $path = "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm";
        $answer = $this->orthancService->importFile($path);
        $this->assertArrayHasKey("ParentStudy", $answer);
        return $answer['ParentStudy'];
    }

    /**
     * @depends testSendDicomFile
     */
    public function testGetStudyOrthancDetails($testingOrthancStudyID)
    {
        $studyDetails = $this->orthancService->getStudyOrthancDetails($testingOrthancStudyID);
        $this->assertInstanceOf(\App\GaelO\Services\StoreObjects\OrthancStudy::class, $studyDetails);
    }

    /**
     * @depends testSendDicomFile
     */
    public function testAnonymizeOrthanc($testingOrthancStudyID)
    {
        $anonymized = $this->orthancService->anonymize(
            $testingOrthancStudyID,
            Constants::ORTHANC_ANON_PROFILE_DEFAULT,
            "code",
            "visit",
            "study"
        );
        //orthanc ID have 44 character lenght
        $this->assertEquals(44, strlen($anonymized));
    }


    public function testOrthancArchiveZip()
    {
        $this->markTestSkipped('SK OK, TO GENERALIZE');
        $seriesIDsArray = ["a66b93bf-d6bb38ab-9b53f65b-e9c39913-8b2969db"];
        $this->orthancService->getOrthancArchiveZip($seriesIDsArray);
    }

    public function testGetOrthancZipStream()
    {
        $this->markTestSkipped('SK OK, TO GENERALIZE');
        $seriesIDsArray = ["cd35ee80-1a6a667d-4084f535-6d6a0494-89cd7dd0"];
        $this->orthancService->getOrthancZipStream($seriesIDsArray);
    }
}

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

        //$this->httpClientSpy  = $this->spy(HttpClientAdapter::class);

        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }

    }

    public function testPeersFunctions()
    {
        $this->orthancService->addPeer('gaelotest', 'http://kanoun.fr:8043', 'salim', 'salim');
        $answer = $this->orthancService->getOrthancPeers();
        $this->assertEquals(1, sizeof($answer));
        $this->orthancService->removeAllPeers();
        $answer = $this->orthancService->getOrthancPeers();
        $this->assertEquals(0, sizeof($answer));
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
    public function testGetStudyStatistics($testingOrthancStudyID){
        $studyStatistics = $this->orthancService->getOrthancRessourcesStatistics('studies', $testingOrthancStudyID);
        $this->assertIsArray($studyStatistics);
    }

    /**
     * @depends testSendDicomFile
     */
    public function testGetOrthancRessourceDetails($testingOrthancStudyID){
        $ressourceDetails = $this->orthancService->getOrthancRessourcesDetails('studies', $testingOrthancStudyID);
        $this->assertIsArray($ressourceDetails);
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
        return $anonymized;
    }

    /**
     * @depends testSendDicomFile
     */
    public function testGetOrthancZipStream($testingOrthancStudyID)
    {
        $seriesIDsArray = [$testingOrthancStudyID];
        $this->orthancService->getOrthancZipStream($seriesIDsArray);
    }

    /**
     * @depends testAnonymizeOrthanc
     */
    public function testDeleteOrthancStudy($anonymizedID){
        $ressourceDetails = $this->orthancService->getOrthancRessourcesDetails('studies', $anonymizedID);
        $data = $this->orthancService->searchInOrthanc( 'studies', '', '', '', $ressourceDetails['MainDicomTags']['StudyInstanceUID'] );
        $this->assertEquals(1, sizeof($data));
        //Delete the anonymized studies
        $this->orthancService->deleteFromOrthanc('studies', $anonymizedID);
        //Check it has gone
        $data = $this->orthancService->searchInOrthanc( 'studies', '', '', '', $ressourceDetails['MainDicomTags']['StudyInstanceUID'] );
        $this->assertEquals(0, sizeof($data));
    }
}

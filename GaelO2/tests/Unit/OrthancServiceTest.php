<?php

namespace Tests\Unit;

use App\GaelO\Constants\Enums\AnonProfileEnum;
use App\GaelO\Services\OrthancService;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Depends;
use Tests\TestCase;

class OrthancServiceTest extends TestCase
{
    private OrthancService $orthancService;

    protected function setUp(): void
    {
        parent::setUp();

        //$this->httpClientSpy  = $this->spy(HttpClientAdapter::class);

        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        $this->markTestSkipped('Need Orthanc Container Running');
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
        $bool = $this->orthancService->isPeerAccelerated('gaelotest');
    }

    public function testOrthancSendToPeer()
    {
        $response = $this->orthancService->sendToPeer('gaelotest', [''], true);
    }

    public function testGetOrthancJobDetails()
    {
        $this->orthancService->getJobDetails('id');
    }

    public function testSendDicomFile()
    {
        $array = [
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm"
        ];
        $answer = $this->orthancService->importFiles($array);
        return $answer[0]['ParentStudy'];
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

    /**
     * @ testSendDicomFile
     */
    public function testGetStudyOrthancDetails($testingOrthancStudyID)
    {
        $studyDetails = $this->orthancService->getStudyOrthancDetails($testingOrthancStudyID);
        $this->assertInstanceOf(\App\GaelO\Services\StoreObjects\OrthancStudy::class, $studyDetails);
    }

    #[Depends("testSendDicomFile")]
    public function testGetStudyStatistics($testingOrthancStudyID)
    {
        $studyStatistics = $this->orthancService->getOrthancRessourcesStatistics('studies', $testingOrthancStudyID);
        $this->assertIsArray($studyStatistics);
    }

    #[Depends("testSendDicomFile")]
    public function testGetOrthancRessourceDetails($testingOrthancStudyID)
    {
        $ressourceDetails = $this->orthancService->getOrthancRessourcesDetails('studies', $testingOrthancStudyID);
        $this->assertIsArray($ressourceDetails);
    }

    #[Depends("testSendDicomFile")]
    public function testAnonymizeOrthanc($testingOrthancStudyID)
    {
        $anonymized = $this->orthancService->anonymize(
            $testingOrthancStudyID,
            AnonProfileEnum::DEFAULT->value,
            "code",
            "id",
            "visit",
            "study",
            null,
            null
        );
        //orthanc ID have 44 character lenght
        $this->assertEquals(44, strlen($anonymized));
        return $anonymized;
    }

    #[Depends("testSendDicomFile")]
    public function testAnonymizeOrthancThroughJobs($testingOrthancStudyID)
    {
        $anonymized = $this->orthancService->anonymizeUsingOrthancJobs(
            $testingOrthancStudyID,
            AnonProfileEnum::DEFAULT->value,
            "code",
            "id",
            "visit",
            "study",
            null,
            null
        );
        //orthanc ID have 44 character lenght
        $this->assertEquals(44, strlen($anonymized));
        return $anonymized;
    }

    #[Depends("testSendDicomFile")]
    public function testGetOrthancZipStream($testingOrthancStudyID)
    {
        $seriesIDsArray = [$testingOrthancStudyID];
        $this->orthancService->getOrthancZipStream($seriesIDsArray);
    }

    #[Depends("testAnonymizeOrthanc")]
    public function testDeleteOrthancStudy($anonymizedID)
    {
        $ressourceDetails = $this->orthancService->getOrthancRessourcesDetails('studies', $anonymizedID);
        $data = $this->orthancService->searchInOrthanc('studies', '', '', '', $ressourceDetails['MainDicomTags']['StudyInstanceUID']);
        $this->assertEquals(1, sizeof($data));
        //Delete the anonymized studies
        $this->orthancService->deleteFromOrthanc('studies', $anonymizedID);
        //Check it has gone
        $data = $this->orthancService->searchInOrthanc('studies', '', '', '', $ressourceDetails['MainDicomTags']['StudyInstanceUID']);
        $this->assertEquals(0, sizeof($data));
    }
}

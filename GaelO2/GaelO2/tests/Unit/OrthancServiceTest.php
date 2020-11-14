<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrthancServiceTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations() {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
        $this->orthancService = App::make(OrthancService::class);
        $this->orthancService->setOrthancServer(false);

        if (false) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testOrthancConnexion(){
       $answer = $this->orthancService->getOrthancPeers();
       $this->assertIsArray($answer);
    }

    public function testOrthancAddPeers() {
        $answer = $this->orthancService->addPeer('gaelotest', 'http://kanoun.fr:8043', 'salim', 'salim');
        $statusCode = $answer->getStatusCode();
        $this->assertEquals(200, $statusCode);
    }

    public function testOrthancPeerIsTransferAccelerated(){
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $bool = $this->orthancService->isPeerAccelerated('gaelotest');
    }

    public function testOrthancSendToPeer(){
        $this->markTestSkipped('SK TO DO WITH REAL SETUP');
        $response = $this->orthancService->sendToPeer('gaelotest', [''], true);
    }

    public function testGetOrthancJobDetails(){
        $this->markTestSkipped('SK TO DO');
        $this->orthancService->getJobDetails('id');
    }

    public function testOrthancDeletePeers(){
        $answer = $this->orthancService->deletePeer('gaelotest');
        $statusCode = $answer->getStatusCode();
        $this->assertEquals(200, $statusCode);

    }

    public function testOrthancRemoveAllPeers(){
        $this->orthancService->removeAllPeers();
        $peers = $this->orthancService->getOrthancPeers();
        $this->assertEmpty($peers);

    }

    public function testSendDicomFileArray(){
        $array = [
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm",
            "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0b7033a437f446e28a999d79ca9901ef.dcm"
        ];
        $answer = $this->orthancService->importFiles($array);
        $this->assertEquals(2, sizeof($answer));
    }

    public function testSendDicomFile(){
        $path = "/home/salim/11009101406003/VR/1.2.840.113704.1.111.2496.1287397130.8/CT_001_0ac8ec19aadc48f698ec8b1eadeecf04.dcm";
        $answer = $this->orthancService->importFile($path);
        $this->assertArrayHasKey("ParentStudy", $answer);
        return $answer['ParentStudy'];
    }

    /**
     * @depends testSendDicomFile
     */
    public function testGetStudyOrthancDetails($testingOrthancStudyID){
        $studyDetails = $this->orthancService->getStudyOrthancDetails($testingOrthancStudyID);
        $this->assertInstanceOf(\App\GaelO\Services\StoreObjects\OrthancStudy::class, $studyDetails);
    }

    /**
     * @depends testSendDicomFile
     */
    public function testAnonymizeOrthanc($testingOrthancStudyID){
        $anonymized = $this->orthancService->anonymize($testingOrthancStudyID, Constants::ORTHANC_ANON_PROFILE_DEFAULT,
                                                "code", "visit", "study");
        //orthanc ID have 44 character lenght
        $this->assertEquals(44, strlen($anonymized));
    }


    public function testOrthancArchiveZip(){
        $this->markTestSkipped('SK OK, TO GENERALIZE');
        $seriesIDsArray = ["a66b93bf-d6bb38ab-9b53f65b-e9c39913-8b2969db"];
        $this->orthancService->getOrthancArchiveZip($seriesIDsArray);
    }

    public function testGetOrthancZipStream(){
        $this->markTestSkipped('SK OK, TO GENERALIZE');
        $seriesIDsArray = ["cd35ee80-1a6a667d-4084f535-6d6a0494-89cd7dd0"];
        $this->orthancService->getOrthancZipStream($seriesIDsArray);
    }

    public function testGetOrthancZipStreamedToLaravel(){
        dd($this->get('api/visits/1/dicoms'));

    }

}

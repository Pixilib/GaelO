<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Models\OrthancSeries;
use App\Models\OrthancStudy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;

class ReverseProxyTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp() : void{
        parent::setUp();

        $this->seriesInstanceUID = '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009';
        $this->studyInstanceUID = '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008';

        $this->orthancStudy = OrthancStudy::factory()
            ->studyUid($this->studyInstanceUID)
            ->create();

        $this->orthancSeries = OrthancSeries::factory()
            ->seriesUid($this->seriesInstanceUID)
            ->orthancStudyId($this->orthancStudy->orthanc_id)
            ->create();

        $this->studyName = $this->orthancStudy->visit->patient->study->name;

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }
    }

    public function testDicomWebReverseProxyStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $response = $this->get('/api/orthanc/dicom-web/studies/'.$this->studyInstanceUID.'/series', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(200);
    }

    public function testDicomWebReverseProxyStudyShouldBeForbiddenNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/orthanc/dicom-web/studies/'.$this->studyInstanceUID.'/series', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(403);
    }

    public function testDicomWebReverseProxySeries(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $response = $this->get('/api/orthanc/dicom-web/studies/'.$this->studyInstanceUID.'/series/'.$this->seriesInstanceUID.'/metadata', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(200);
    }

    public function testDicomWebReverseProxySeriesShouldBeForbiddenNoRole(){
        $response = $this->get('/api/orthanc/dicom-web/studies/'.$this->studyInstanceUID.'/series/'.$this->seriesInstanceUID.'/metadata', ['Accept'=>'application/json', 'gaelorole'=>['Supervisor']]);
        $response->assertStatus(403);
    }

    public function testTusReverseProxy()
    {
        $response = $this->get('/api/tus');
        $response->assertStatus(200);
    }
}

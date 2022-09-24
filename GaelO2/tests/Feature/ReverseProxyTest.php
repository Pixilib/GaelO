<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class ReverseProxyTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        $this->seriesInstanceUID = '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11009';
        $this->studyInstanceUID = '1.2.826.0.1.3680043.5.2014.6.4.10.27.08.20160309120853.168.11008';

        $this->orthancStudy = DicomStudy::factory()
            ->studyUid($this->studyInstanceUID)
            ->create();

        $this->orthancSeries = DicomSeries::factory()
            ->seriesUid($this->seriesInstanceUID)
            ->studyInstanceUID($this->orthancStudy->study_uid)
            ->create();

        $this->studyName = $this->orthancStudy->visit->patient->study->name;

        $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');

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

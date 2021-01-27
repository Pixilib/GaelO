<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\OrthancSeries;
use Tests\AuthorizationTools;

class DicomTest extends TestCase
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
        $this->orthancSeries = OrthancSeries::factory()->create();
        $this->visitId = $this->orthancSeries->orthancStudy->visit->id;
        $this->studyName = $this->orthancSeries->orthancStudy->visit->patient->study->name;

    }

    public function testGetOrthancZip(){
        $this->markTestSkipped('Needs Orthanc To Be Tested');
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $answer = $this->get('api/visits/1/dicoms/file?role=Investigator');
        $answer->assertStatus(200);

    }

    public function testGetOrthancShouldFailBeacauseNoRole(){
        $this->markTestSkipped('Needs Orthanc To Be Tested');
        $answer = $this->get('api/visits/1/dicoms/file?role=Investigator');
        $answer->assertStatus(403);
    }

    public function testGetDicomsDataInvestigator(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $answer = $this->get('api/visits/'.$this->visitId.'/dicoms?role=Investigator');
        $response = json_decode($answer->content(), true);
        $this->assertEquals(1, sizeof($response));

    }

    public function testGetDicomsDataSupervisor(){
        $this->orthancSeries->orthancStudy->delete();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->get('api/visits/'.$this->visitId.'/dicoms?role=Supervisor');
        $response = json_decode($answer->content(), true);
        $this->assertEquals(1, sizeof($response));
        $this->assertEquals(true, $response[0]['deleted']);

    }

    public function testGetDicomsDataFailNoRole(){
        AuthorizationTools::actAsAdmin(false);
        $this->get('api/visits/'.$this->visitId.'/dicoms?role=Investigator')->assertStatus(403);
    }

}

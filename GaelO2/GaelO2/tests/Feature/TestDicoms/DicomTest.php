<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\DicomSeries;
use App\Models\User;
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
        $this->dicomSeries = DicomSeries::factory()->create();
        $this->visitId = $this->dicomSeries->dicomStudy->visit->id;
        $this->studyName = $this->dicomSeries->dicomStudy->visit->patient->study->name;

    }

    public function testGetOrthancZip(){
        $this->markTestSkipped('Needs Orthanc To Be Tested');
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $answer = $this->get('api/studies/'.$this->studyName.'/visits/1/dicoms/file?role=Investigator');
        $answer->assertStatus(200);

    }

    public function testGetOrthancShouldFailBeacauseNoRole(){
        $this->markTestSkipped('Needs Orthanc To Be Tested');
        $answer = $this->get('api/studies/'.$this->studyName.'/visits/1/dicoms/file?role=Investigator');
        $answer->assertStatus(403);
    }

    public function testGetDicomsDataInvestigator(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        //Testing investigator so need to be linked with same center
        $userModel = User::find($currentUserId);
        $userModel->center_code = $this->dicomSeries->dicomStudy->visit->patient->center_code;
        $userModel->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $answer = $this->get('api/studies/'.$this->studyName.'/visits/'.$this->visitId.'/dicoms?role=Investigator');
        $answer->assertStatus(200);
        $response = json_decode($answer->content(), true);
        $this->assertEquals(1, sizeof($response));

    }

    public function testGetDicomsDataInvestigatorDeletedStudy(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->dicomSeries->dicomStudy->delete();

        //Testing investigator so need to be linked with same center
        $userModel = User::find($currentUserId);
        $userModel->center_code = $this->dicomSeries->dicomStudy->visit->patient->center_code;
        $userModel->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $answer = $this->get('api/studies/'.$this->studyName.'/visits/'.$this->visitId.'/dicoms?role=Investigator');
        $answer->assertStatus(200);
        $response = json_decode($answer->content(), true);
        $this->assertEquals(0, sizeof($response));

    }

    public function testGetDicomsDataSupervisor(){
        $this->dicomSeries->dicomStudy->delete();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $answer = $this->get('api/studies/'.$this->studyName.'/visits/'.$this->visitId.'/dicoms?role=Supervisor');
        $answer->assertStatus(200);
        $response = json_decode($answer->content(), true);
        $this->assertEquals(true, $response[0]['deleted']);

    }

    public function testGetDicomsDataFailNoRole(){
        AuthorizationTools::actAsAdmin(false);
        $this->get('api/studies/'.$this->studyName.'/visits/'.$this->visitId.'/dicoms?role=Investigator')->assertStatus(403);
    }

}

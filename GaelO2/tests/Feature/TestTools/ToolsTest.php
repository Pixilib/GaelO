<?php

namespace Tests\Feature\TestTools;

use App\GaelO\Constants\Constants;
use App\Models\Center;
use App\Models\Patient;
use App\Models\User;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ToolsTest extends TestCase
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

        $study = Study::factory()->name('TEST')->create();
        $center = Center::factory()->code(1)->create();
        $patient = Patient::factory()->studyName($study->name)->centerCode($center->code)->create();
        $this->studyName = $study->name;
        $this->centerCode = $center->code;
        $this->patientId = $patient->id;
    }

    public function testGetPatientsInStudyFromCenters() {
        $center = Center::factory()->code(2)->create();
        Patient::factory()->studyName($this->studyName)->centerCode($this->centerCode)->count(9)->create();
        Patient::factory()->studyName($this->studyName)->centerCode($center->code)->count(10)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['centerCodes']  = [ $this->centerCode, $center->code ];
        $validPayload['studyName'] = $this->studyName;

        $answer = $this->json('POST', 'api/tools/centers/patients-from-centers', $validPayload);
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);
        $this->assertEquals(20, sizeof($content));
    }

    public function testGetPatientsInStudyFromCentersForbiddenNotSupervisor() {
        AuthorizationTools::actAsAdmin(false);

        $validPayload['centerCodes']  = [ $this->centerCode ];
        $validPayload['studyName'] = $this->studyName;

        $answer = $this->json('POST', 'api/tools/centers/patients-from-centers', $validPayload);
        $answer->assertStatus(403);
    }

    public function testGetPatientsVisitsInStudy() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['patientIds'] = [ $this->patientId ];
        $answer = $this->json('POST', 'api/tools/patients/visits-from-patients?studyName='.$this->studyName, $validPayload);
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);
    }

    public function testGetPatientsVisitsInStudyForbiddenNotSupervisor() {
        AuthorizationTools::actAsAdmin(false);

        $validPayload['patientIds'] = [ $this->patientId ];
        $answer = $this->json('POST', 'api/tools/patients/visits-from-patients?studyName='.$this->studyName, $validPayload);
        $answer->assertStatus(403);
    }

    public function testFindUser() {
        User::factory()->email('test@test.fr')->create();
        
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['email'] = 'test@test.fr';
        $answer = $this->json('POST', 'api/tools/find-user?studyName='.$this->studyName, $validPayload);
        $answer->assertSuccessful();
    }

    public function testFindUserDoesNotExist() {        
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['email'] = 'test@test.fr';
        $answer = $this->json('POST', 'api/tools/find-user?studyName='.$this->studyName, $validPayload);
        $answer->assertStatus(404);
    }

    public function testFindUserForbiddenNotSupervisor() {
        AuthorizationTools::actAsAdmin(false);

        $validPayload['email'] = 'test@test.fr';
        $answer = $this->json('POST', 'api/tools/find-user?studyName='.$this->studyName, $validPayload);
        $answer->assertStatus(403);
    }

}

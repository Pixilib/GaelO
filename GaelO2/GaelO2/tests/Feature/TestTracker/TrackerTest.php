<?php

namespace Tests\Feature\TestTracker;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Study;
use App\GaelO\Constants\Constants;
use Tests\AuthorizationTools;

class TrackerTest extends TestCase
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
        $this->study = Study::factory()->create();
        $this->visit = Visit::factory()->create();
    }


    public function testGetTracker () {
        AuthorizationTools::actAsAdmin(true);
        //Test that tracker routes work properly
        $this->json('GET', '/api/tracker?admin=false')->assertSuccessful();
        $this->json('GET', '/api/tracker?admin=true')->assertSuccessful();
    }

    public function testGetTrackerForbiddenNotAdmin(){
        //To be changed when supervisor implemented
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/tracker?admin=false')->assertStatus(403);
    }

    public function testGetStudyTracker () {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/'.$this->study->name.'/tracker?role=Supervisor&action=Create Visit')->assertSuccessful();
    }

    public function testGetStudyTrackerShouldFailNotSupervisor () {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/'.$this->study->name.'/tracker?role=Supervisor&action=Create Visit')->assertStatus(403);
    }

    public function testGetStudyTrackerByVisit () {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/'.$this->study->name.'/visits/'.$this->visit->id.'/tracker')->assertSuccessful();
    }

    public function testGetStudyTrackerByVisitShouldFailNotSupervisor () {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/'.$this->study->name.'/visits/'.$this->visit->id.'/tracker')->assertStatus(403);
    }

}

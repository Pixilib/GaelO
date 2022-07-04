<?php

namespace Tests\Feature\TestTracker;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\GaelO\Constants\Constants;
use App\Models\Tracker;
use App\Models\Visit;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->visit = Visit::factory()->create();
        $this->study = $this->visit->patient->study;
    }


    public function testGetTracker()
    {
        AuthorizationTools::actAsAdmin(true);
        //Test that tracker routes work properly
        $this->json('GET', '/api/tracker?admin=false')->assertSuccessful();
        $this->json('GET', '/api/tracker?admin=true')->assertSuccessful();
    }

    public function testGetTrackerForbiddenNotAdmin()
    {
        //To be changed when supervisor implemented
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/tracker?admin=false')->assertStatus(403);
    }

    public function testGetStudyMessageTracker()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/tracker/messages')->assertSuccessful();
    }

    public function testGetStudyMessageTrackerShouldFailNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/tracker/messages')->assertStatus(403);
    }

    public function testGetStudyTracker()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/tracker/Supervisor?action=Create Visit')->assertSuccessful();
    }

    public function testGetStudyTrackerCorrectiveAction()
    {
        Tracker::factory()->actionType(Constants::TRACKER_CORRECTIVE_ACTION)->visitId($this->visit->id)->studyName($this->study->name)->role(Constants::ROLE_INVESTIGATOR)->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/tracker/Investigator?action=Corrective Action')->assertSuccessful();
        $data = json_decode($response->content(), true);
        $this->assertEquals(1, sizeof($data));
    }

    public function testGetStudyTrackerShouldFailNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/tracker/Supervisor?action=Create Visit')->assertStatus(403);
    }

    public function testGetStudyTrackerByVisit()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/tracker')->assertSuccessful();
    }

    public function testGetStudyTrackerByVisitShouldFailNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $response = $this->json('GET', '/api/studies/' . $this->study->name . '/visits/' . $this->visit->id . '/tracker')->assertStatus(403);
    }
}

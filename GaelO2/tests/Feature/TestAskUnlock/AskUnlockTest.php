<?php

namespace Tests\Feature\TestAskUnlock;

use App\GaelO\Constants\Constants;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use Tests\AuthorizationTools;

class AskUnlockTest extends TestCase
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
        ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->visit->patient->study_name)->reviewAvailable()->create();
        $this->studyName = $this->visit->patient->study->name;
        $this->patientCenter = $this->visit->patient->center->code;
    }

    public function testAskUnlockInvestigator()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($userId, $this->patientCenter);

        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/visits/' . $this->visit->id . '/ask-unlock?role=Investigator&studyName='.$this->studyName, $payload);
        $response->assertStatus(200);
    }

    public function testAskUnlockReviewer()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/visits/' . $this->visit->id . '/ask-unlock?role=Reviewer&studyName='.$this->studyName, $payload);
        $response->assertStatus(200);
    }

    public function testAskUnlockFailBecauseMessageEmpty()
    {
        $userId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($userId, $this->patientCenter);

        $response = $this->post('api/visits/' . $this->visit->id . '/ask-unlock?role=Investigator&studyName='.$this->studyName, []);
        $response->assertStatus(400);
    }

    public function testAskUnlockShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'message' => 'My Message'
        ];
        $response = $this->post('api/visits/' . $this->visit->id . '/ask-unlock?role=Investigator&studyName='.$this->studyName, $payload);
        $response->assertStatus(403);
    }
}
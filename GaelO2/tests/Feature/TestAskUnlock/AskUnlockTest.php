<?php

namespace Tests\Feature\TestAskUnlock;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\ReviewStatus;
use Tests\TestCase;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class AskUnlockTest extends TestCase
{
    use RefreshDatabase;
    private Visit $visit;
    private MockInterface $trackerSpy;
    private string $studyName;
    private string $patientCenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visit = Visit::factory()->create();
        ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->visit->patient->study_name)->reviewAvailable()->create();
        $this->studyName = $this->visit->patient->study->name;
        $this->patientCenter = $this->visit->patient->center->code;
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::ROLE_INVESTIGATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_ASK_UNLOCK, Mockery::any());
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($userId, Constants::ROLE_REVIEWER, Mockery::any(), Mockery::any(), Constants::TRACKER_ASK_UNLOCK, Mockery::any());
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

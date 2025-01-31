<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Repositories\TrackerRepository;
use Tests\TestCase;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class DeleteVisitTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private Visit $visit;
    private string $studyName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visit = Visit::factory()->create();
        $this->studyName = $this->visit->patient->study->name;
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }


    public function testDeleteVisit()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'reason' => 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/' . $this->visit->id . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $resp->assertStatus(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_DELETE_VISIT, Mockery::any());
    }

    public function testDeleteVisitShouldFailWrongStudy()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'reason' => 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/' . $this->visit->id . '?role=Supervisor&studyName=' . $this->studyName . 'wrong', $payload);
        $resp->assertStatus(403);
    }

    public function testDeleteVisitShouldFailNoReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $resp = $this->json('DELETE', 'api/visits/' . $this->visit->id . '?role=Supervisor&studyName=' . $this->studyName);
        $resp->assertStatus(400);
    }

    public function testDeleteVisitShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'reason' => 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/' . $this->visit->id . '?role=Supervisor&studyName=' . $this->studyName, $payload);
        $resp->assertStatus(403);
    }

    public function testDeleteVistByInvestigatorFailQcDone()
    {

        $visit = Visit::factory()->stateQualityControl(QualityControlStateEnum::ACCEPTED->value)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $visit->patient->study->name);

        $payload = [
            'reason' => 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/' . $visit->id . '?role=Investigator&studyName=' . $this->studyName, $payload);
        $resp->assertStatus(403);
    }
}

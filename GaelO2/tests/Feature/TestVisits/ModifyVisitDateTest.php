<?php


namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class ModifyVisitDateTest extends TestCase {

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private Visit $visit;
    private string $studyName;

    protected function setUp() : void {

        parent::setUp();
        $this->artisan('db:seed');
        $this->visit = Visit::factory()->create();
        $this->studyName = $this->visit->patient->study_name;
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testModifyVisitDate()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'visitDate' => now(),
            'reason' => 'changeDate'
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName, $payload);

        $response->assertStatus(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_UPDATE_VISIT_DATE, Mockery::any());

    }

    public function testModifyVisitDateShouldFailMissingReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'visitDate' => now(),
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName, $payload);

        $response->assertStatus(400);

    }

    public function testModifyVisitDateShouldFailWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'visitDate' => now(),
            'reason' => 'changeDate'
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName. 'wrong', $payload);

        $response->assertStatus(403);

    }

    public function testModifyVisitDateShouldFailNoRole()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $payload = [
            'visitDate' => now(),
            'reason' => 'changeDate'
        ];

        $response = $this->put('/api/visits/'.$this->visit->id.'/visit-date?studyName='.$this->studyName, $payload);

        $response->assertStatus(403);

    }


}

<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use Tests\TestCase;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class DeleteStudyTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testDeleteStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/' . $study->name, ['reason' => 'study finished'])->assertSuccessful();
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_DEACTIVATE_STUDY, Mockery::any());
    }

    public function testDeleteStudyShouldFailNoReason()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/' . $study->name)->assertStatus(400);
    }

    public function testReactivateStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = ['reason' => 'need new analysis'];
        $this->json('POST', '/api/studies/' . $studyName . '/activate', $payload)->assertNoContent(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_REACTIVATE_STUDY, Mockery::any());
    }

    public function testReactivateStudyShouldFailNoReason()
    {
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = [];
        $this->json('POST', '/api/studies/' . $studyName . '/activate', $payload)->assertStatus(400);
    }

    public function testReactivateStudyForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $study->delete();
        $payload = ['reason' => 'need new analysis'];
        $this->json('POST', '/api/studies/' . $study->name . '/activate', $payload)->assertStatus(403);
    }
}

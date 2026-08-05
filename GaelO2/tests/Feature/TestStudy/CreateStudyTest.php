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

class CreateStudyTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->payload = [
            'name' => 'NEWSTUDY',
            'code' => '1234',
            'patientCodeLength' => 5,
            'controllerShowAll' => true,
            'monitorShowAll' => false,
            'documentationMandatory' => false,
            'contactEmail' => 'test@gaelo.fr',
            'creatablePatientsInvestigator' => false,
            'allowAlphaPatientCode' => false
        ];
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testCreateStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $this->json('POST', '/api/studies', $this->payload)->assertNoContent(201);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_CREATE_STUDY, Mockery::any());
    }

    public function testCreateStudyShouldFailBecauseNotAlfaNumerical()
    {
        AuthorizationTools::actAsAdmin(true);

        $this->payload['name'] = 'NEWSTUDy';
        
        $this->json('POST', '/api/studies', $this->payload)->assertStatus(400);

        $this->payload['name'] = 'NEW STUDY';

        $this->json('POST', '/api/studies', $this->payload)->assertStatus(400);

        $this->payload['name'] = 'NEW.STUDY';
        $this->json('POST', '/api/studies', $this->payload)->assertStatus(400);
    }

    public function testCreateAncillaryStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->payload['ancillaryOf'] = $study->name;

        $this->json('POST', '/api/studies', $this->payload)->assertStatus(201);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::TRACKER_ROLE_ADMINISTRATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_CREATE_STUDY, Mockery::any());
    }

    public function testCreateStudyForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('POST', '/api/studies', $this->payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->count(2)->create()->first();
        $this->payload['name'] = $study->name;
        $this->json('POST', '/api/studies', $this->payload)->assertStatus(409);
    }

    public function testCreateStudyWith0Length()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->payload['patientCodeLength'] = 0;
        $this->json('POST', '/api/studies', $this->payload)->assertStatus(400);
    }
}

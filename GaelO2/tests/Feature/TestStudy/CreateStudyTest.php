<?php

namespace Tests\Feature\TestStudy;

use Tests\TestCase;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class CreateStudyTest extends TestCase
{

    use RefreshDatabase;

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
            'investigatorOwnVisits' => false
        ];
    }

    public function testCreateStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $this->json('POST', '/api/studies', $this->payload)->assertNoContent(201);
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
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->payload['ancillaryOf'] = $study->name;

        $this->json('POST', '/api/studies', $this->payload)->assertStatus(201);
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

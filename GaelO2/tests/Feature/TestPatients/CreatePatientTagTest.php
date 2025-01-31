<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\AuthorizationTools;

class CreatePatientTagTest extends TestCase
{
    use RefreshDatabase;
    private Study $study;
    private Patient $patient;
    private MockInterface $trackerSpy;
    private string $studyName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        //Fill patient table
        $this->study = Study::factory()->create();
        $this->studyName = $this->study->name;
        $this->patient = Patient::factory()->studyName($this->studyName)->metadata(['tags'=>['Salim']])->create();
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    public function testCreatePatientTag()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload =['tag'=>'DLBCL'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(201);
        $patient = Patient::find($this->patient->id);
        $this->assertContains('DLBCL', $patient['metadata']['tags']);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_PATIENT, Mockery::any());
    }

    public function testCreatePatientTagShouldNotAcceptSpaces()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload =['tag'=>'DLBC L'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(400);
    }

    public function testCreatePatientTagShouldBeRefusedExisting()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload =['tag'=>'Salim'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(409);
    }

    public function testCreatePatientTagShouldBeRefusedNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $payload =['tag'=>'DLBCL'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(403);
    }

    public function testCreatePatientTagShouldBeRefusedWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $study = Study::factory()->create();

        $payload =['tag'=>'DLBCL'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $study->name, $payload)->assertStatus(403);
    }

}

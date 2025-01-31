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

class DeletePatientTagTest extends TestCase
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

    public function testDeletePatientTag()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->json('DELETE', '/api/patients/' . $this->patient->id . '/metadata/tags/Salim?studyName=' . $this->studyName)->assertStatus(200);
        $patient = Patient::find($this->patient->id);
        $this->assertNotContains('Salim', $patient['metadata']['tags']);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_EDIT_PATIENT, Mockery::any());
    }

    public function testDeletePatientTagNotExisting()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->json('DELETE', '/api/patients/' . $this->patient->id . '/metadata/tags/DLBCL?studyName=' . $this->studyName)->assertStatus(404);
    }

    public function testDeletePatientTagShouldBeRefusedNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $this->json('DELETE', '/api/patients/' . $this->patient->id . '/metadata/tags/Salim?studyName=' . $this->studyName)->assertStatus(403);
    }

    public function testDeletePatientTagShouldBeRefusedWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $study = Study::factory()->create();

        $this->json('DELETE', '/api/patients/' . $this->patient->id . '/metadata/tags/Salim?studyName=' . $study->name)->assertStatus(403);
    }

}

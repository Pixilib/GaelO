<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class CreatePatientTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        //Fill patient table
        $this->study = Study::factory()->create();
        $this->studyName = $this->study->name;
        $this->patient = Patient::factory()->studyName($this->studyName)->metadata(['tags'=>['Salim']])->create();
    }

    public function testCreatePatientTag()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload =['tag'=>'DLBCL'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(201);
        $patient = Patient::find($this->patient->id);
        $this->assertContains('DLBCL', $patient['metadata']['tags']);
    }

    public function testCreatePatientTagShouldBeRefusedExisting()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $payload =['tag'=>'Salim'];

        $this->json('POST', '/api/patients/' . $this->patient->id . '/metadata/tags?studyName=' . $this->studyName, $payload)->assertStatus(400);
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

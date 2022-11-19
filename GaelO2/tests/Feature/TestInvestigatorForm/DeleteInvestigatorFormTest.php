<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Review;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DeleteInvestigatorFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $this->visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        $this->review = Review::factory()->visitId($this->visit->id)->studyName('TEST')->create();
        $this->studyName = "TEST";
    }

    public function testDeleteInvestigatorForm()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->delete('api/visits/' . $this->review->visit_id . '/investigator-form?studyName=' . $this->studyName, $payload)->assertSuccessful();
    }

    public function testDeleteInvestigatorFormShouldFailWrongStudy()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->delete('api/visits/' . $this->review->visit_id . '/investigator-form?studyName=' . $this->studyName.'wrong', $payload)->assertStatus(403);
    }

    public function testDeleteInvestigatorFormShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->delete('api/visits/' . $this->review->visit_id . '/investigator-form?studyName=' . $this->studyName, $payload)->assertStatus(403);
    }

    public function testDeleteInvestigatorFormShouldFailNoReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => ''
        ];

        $this->delete('api/visits/' . $this->review->visit_id . '/investigator-form?studyName=' . $this->studyName, $payload)->assertStatus(400);
    }
}

<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Review;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class DeleteInvestigatorFormTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->review = Review::factory()->create();
        $this->studyName = $this->review->visit->patient->study_name;
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

<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Review;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UnlockInvestigatorFormTest extends TestCase
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
        $this->review = Review::factory()->validated()->create();
        $this->studyName = $this->review->visit->patient->study_name;
    }

    public function testUnlockInvestigatorForm()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/' . $this->review->visit_id . '/investigator-form/unlock?studyName=' . $this->studyName, $payload)->assertStatus(200);
    }


    public function testUnlockInvestigatorFormShouldFailWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/' . $this->review->visit_id . '/investigator-form/unlock?studyName=' . $this->studyName . 'wrong', $payload)->assertStatus(403);
    }

    public function testUnlockInvestigatorFormShouldFailedAlreadyUnlocked()
    {
        $this->review->validated = false;
        $this->review->save();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/' . $this->review->visit_id . '/investigator-form/unlock?studyName=' . $this->studyName, $payload)->assertStatus(400);
    }

    public function testUnlockInvestigatorFormShouldFailedNoReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $payload = [
            'reason' => ''
        ];

        $this->patch('api/visits/' . $this->review->visit_id . '/investigator-form/unlock?studyName=' . $this->studyName, $payload)->assertStatus(400);
    }

    public function testUnlockInvestigatorFormShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/' . $this->review->visit_id . '/investigator-form/unlock?studyName=' . $this->studyName, $payload)->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CreatableVisitTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $visitType = VisitType::factory()->create();
        $this->studyName = $visitType->visitGroup->study->name;
        $patient = Patient::factory()->studyName($this->studyName)->create();
        $this->patientCode = $patient->code;
    }

    public function testGetCreatableVisit()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $response = $this->get('/api/studies/' . $this->studyName . '/patients/' . $this->patientCode . '/creatable-visits');
        $response->assertStatus(200);
    }

    public function testGetCreatableVisitShouldFailNoRole()
    {
        $response = $this->get('/api/studies/' . $this->studyName . '/patients/' . $this->patientCode . '/creatable-visits');
        $response->assertStatus(403);
    }
}

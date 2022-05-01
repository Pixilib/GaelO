<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitGroup;
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
        $this->patient = Patient::factory()->inclusionStatus(Constants::PATIENT_INCLUSION_STATUS_INCLUDED)->create();
        $this->studyName = $this->patient->study_name;
        $this->patientId = $this->patient->id;
        //Create a Visit Type to have one creatable visit
        $visitGroup = VisitGroup::factory()->studyName($this->studyName)->create();
        VisitType::factory()->visitGroupId($visitGroup->id)->create();
    }

    public function testGetCreatableVisit()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        $userEntity = User::find($currentUserId);
        $userEntity->center_code = $this->patient->center_code;
        $userEntity->save();

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $response = $this->get('/api/patients/' . $this->patientId . '/creatable-visits');
        $responseArray = json_decode( $response->content() );
        $this->assertEquals(1, sizeof($responseArray));
        $response->assertStatus(200);
    }

    public function testGetCreatableVisitShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/patients/' . $this->patientId . '/creatable-visits');
        $response->assertStatus(403);
    }
}
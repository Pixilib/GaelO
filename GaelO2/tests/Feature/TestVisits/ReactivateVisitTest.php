<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Tests\TestCase;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class ReactivateVisitTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
        $this->visit = Visit::factory()->create();
        $this->visit->delete();
    }


    public function testReactivateTest(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->visit->patient->study_name);

        $this->json('POST', 'api/visits/'.$this->visit->id.'/activate')->assertStatus(200);

    }

    public function testReactivateTestShouldFailNoRole(){

        AuthorizationTools::actAsAdmin(false);
        $this->json('POST', 'api/visits/'.$this->visit->id.'/activate')->assertStatus(403);

    }

    public function testReactivateTestShouldFailAlreadyExistingVisit(){

        Visit::factory()->visitTypeId($this->visit->visit_type_id)->patientId($this->visit->patient_id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->visit->patient->study_name);

        $this->json('POST', 'api/visits/'.$this->visit->id.'/activate')->assertStatus(409);

    }

    public function testReactivateTestShouldFailNotDeleted(){

        $this->visit->restore();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->visit->patient->study_name);

        $this->json('POST', 'api/visits/'.$this->visit->id.'/activate')->assertStatus(409);

    }

}

<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use Tests\AuthorizationTools;

class ReactivateVisitTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void {
        parent::setUp();

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

<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use Tests\AuthorizationTools;

class DeleteVisitTest extends TestCase
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
    }


    public function testDeleteVisit(){

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->visit->patient->study->name);

        $payload = [
            'reason'=> 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/'.$this->visit->id.'?role=Supervisor', $payload);
        $resp->assertStatus(200);
    }

    public function testDeleteVisitShouldFailNoReason(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->visit->patient->study->name);

        $resp = $this->json('DELETE', 'api/visits/'.$this->visit->id.'?role=Supervisor');
        $resp->assertStatus(400);
    }

    public function testDeleteVisitShouldFailNoRole(){
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'reason'=> 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/'.$this->visit->id.'?role=Supervisor', $payload);
        $resp->assertStatus(403);

    }

    public function testDeleteVistByInvestigatorFailQcDone(){

        $visit = Visit::factory()->stateQualityControl(Constants::QUALITY_CONTROL_ACCEPTED)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $visit->patient->study->name);

        $payload = [
            'reason'=> 'false visit'
        ];

        $resp = $this->json('DELETE', 'api/visits/'.$visit->id.'?role=Investigator', $payload);
        $resp->assertStatus(403);
    }
}

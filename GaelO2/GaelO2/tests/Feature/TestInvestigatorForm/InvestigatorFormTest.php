<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\AuthorizationService;
use App\Models\Review;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use League\OAuth2\Server\AuthorizationServer;
use Tests\AuthorizationTools;
use Tests\TestCase;

class InvestigatorFormTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
    }

    public function testGetInvestigatorForm(){
        $review = Review::factory()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $response = $this->get('api/visits/'.$review->visit_id.'/investigator-form?role=Supervisor');
        dd($response);
    }

}

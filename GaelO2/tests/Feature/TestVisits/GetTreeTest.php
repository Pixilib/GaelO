<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class GetTreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        $reviewStatus = ReviewStatus::factory()->create();
        $this->studyName = $reviewStatus->visit->patient->study_name;
    }

    public function testGetTreeInvestigator()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $response = $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Investigator');
        $response->assertStatus(200);
    }

    public function testGetTreeReviewer()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);
        $response = $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Reviewer');
        $response->assertStatus(200);
    }


    public function testGetTreeController()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);
        $response = $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Controller');
        $response->assertStatus(200);
    }

    public function testGetTreeMonitor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_MONITOR, $this->studyName);
        $response = $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Monitor');
        $response->assertStatus(200);
    }


    public function testGetTreeSupervisorShouldFail()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);
        $response = $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Supervisor');
        $response->assertStatus(400);
    }


    public function testGetTreeForbiddenNoRole(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $this->get('/api/studies/'.$this->studyName.'/visits-tree?role=Controller')->assertStatus(403);

    }
}

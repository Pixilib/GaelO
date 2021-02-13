<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Review;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
        $this->get('api/visits/'.$review->visit_id.'/investigator-form?role=Supervisor')->assertSuccessful();

    }

    public function testDeleteInvestigatorForm(){
        $review = Review::factory()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->delete('api/visits/'.$review->visit_id.'/investigator-form',$payload)->assertSuccessful();

    }

    public function testDeleteInvestigatorFormShouldFailNoRole(){
        $review = Review::factory()->create();
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->delete('api/visits/'.$review->visit_id.'/investigator-form',$payload)->assertStatus(403);

    }

    public function testDeleteInvestigatorFormShouldFailNoReason(){
        $review = Review::factory()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $payload = [
            'reason' => ''
        ];

        $this->delete('api/visits/'.$review->visit_id.'/investigator-form', $payload)->assertStatus(400);

    }

    public function testCreateInvestigatorForm(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['lugano' => 'CR'],
            'validated' => true
        ];

        $this->post('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(201);

    }

}

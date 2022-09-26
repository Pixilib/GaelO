<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Review;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;

class InvestigatorFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetInvestigatorForm(){
        $review = Review::factory()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $studyName = $review->visit->patient->study_name;
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName );
        $this->get('api/visits/'.$review->visit_id.'/investigator-form?role=Supervisor&studyName='.$studyName)->assertSuccessful();

    }

    public function testGetInvestigatorFormMetadata(){
        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);

        $answer = $this->get('api/studies/'.$study->name.'/investigator-forms/metadata?visitType='.$visitType->id);
        $answer->assertStatus(200);

    }

    public function testGetInvestigatorFormMetadataShouldFailNotSupervisor(){
        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->get('api/studies/'.$study->name.'/investigator-forms/metadata?visitType='.$visitType->id);
        $answer->assertStatus(403);

    }

    public function testGetAssociatedDataForInvestigator(){

        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $answer = $this->get('api/visits/'.$visit->id.'/investigator-associated-data?role=Investigator');
        $content= json_decode($answer->content(), true);
        $this->assertArrayHasKey('LastChemo', $content);
        $answer->assertStatus(200);
    }

}

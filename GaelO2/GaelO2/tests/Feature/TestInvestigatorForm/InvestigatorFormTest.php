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

    public function testUnlockInvestigatorForm(){
        $review = Review::factory()->validated()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/'.$review->visit_id.'/investigator-form/unlock',$payload)->assertStatus(200);

    }

    public function testUnlockInvestigatorFormShouldFailedAlreadyUnlocked(){
        $review = Review::factory()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/'.$review->visit_id.'/investigator-form/unlock',$payload)->assertStatus(400);

    }

    public function testUnlockInvestigatorFormShouldFailedNoReason(){
        $review = Review::factory()->validated()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->visit->patient->study_name);
        $payload = [
            'reason' => ''
        ];

        $this->patch('api/visits/'.$review->visit_id.'/investigator-form/unlock',$payload)->assertStatus(400);

    }

    public function testUnlockInvestigatorFormShouldFailNoRole(){
        $review = Review::factory()->validated()->create();
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'reason' => 'wrong Form'
        ];

        $this->patch('api/visits/'.$review->visit_id.'/investigator-form/unlock',$payload)->assertStatus(403);

    }

    public function testCreateInvestigatorForm(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->post('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(201);



    }


    public function testCreateInvestigatorFormShouldFailNoRole(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->post('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);



    }

    public function testCreateInvestigatorFormShouldFailInvestigatorFormNotNeeded(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->post('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);



    }

    public function testCreateInvestigatorFormShouldFailInvestigatorFormAlreadyExisting(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DRAFT)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->post('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);



    }


    public function testModifyInvestigatorForm(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        Review::factory()->visitId($visit->id)->studyName($study->name)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->put('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(200);

    }

    public function testModifyInvestigatorFormShouldFailNoRole(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        Review::factory()->visitId($visit->id)->studyName($study->name)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->put('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);

    }

    public function testModifyInvestigatorFormShouldFailAlreadyValidated(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)->visitTypeId($visitType->id)->create();

        Review::factory()->visitId($visit->id)->studyName($study->name)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->put('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);

    }

    public function testModifyInvestigatorFormShouldFailInvestigatorFormNotNeeded(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        Review::factory()->visitId($visit->id)->studyName($study->name)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->put('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(403);

    }

    public function testModifyInvestigatorFormShouldFailNotExistingForm(){
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $patient->center_code);

        $payload = [
            'data' => ['comment' => 'CR'],
            'validated' => true
        ];

        $this->put('api/visits/'.$visit->id.'/investigator-form', $payload)->assertStatus(404);

    }


    public function testGetInvestigatorFormMetadata(){
        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);

        $answer = $this->get('api/studies/'.$study->name.'/visit-types/'.$visitType->id.'/investigator-forms/metadata');
        $answer->assertStatus(200);

    }

    public function testGetInvestigatorFormMetadataShouldFailNotSupervisor(){
        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->get('api/studies/'.$study->name.'/visit-types/'.$visitType->id.'/investigator-forms/metadata');
        $answer->assertStatus(403);

    }



}

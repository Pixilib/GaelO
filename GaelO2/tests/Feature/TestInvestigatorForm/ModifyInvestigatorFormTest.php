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

class ModifyInvestigatorFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $this->studyName = $study->name;
        $this->centerCode = $patient->center_code;
        $this->visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        Review::factory()->visitId($this->visit->id)->sentFiles(['41'=>'path'])->studyName($study->name)->create();
    }


    private $validFormPayload = [
        'comments' => 'test',
        'glycaemia' => 5,
        'glycaemiaNotDone' => false,
        'radiotherapyThreeMonths' => true,
        'csfThreeWeeks' => true,
        'biopsy' => false,
        'biopsyDate' => null,
        'biopsyLocation' => null,
        'infection' => true,
        'infectionDate' => '01/29/2021',
        'infectionLocation' => 5,
    ];

    public function testModifyInvestigatorForm()
    {


        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(200);
    }


    public function testModifyInvestigatorFormShouldFailedValidationContent()
    {


        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => [...$this->validFormPayload, 'glycaemiaNotDone' => null],
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(400);
    }

    public function testModifyInvestigatorFormShouldFailNoRole()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }

    public function testModifyInvestigatorFormShouldFailAlreadyValidated()
    {
        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DONE;
        $this->visit->save();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }

    public function testModifyInvestigatorFormShouldFailInvestigatorFormNotNeeded()
    {
        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        $this->visit->save();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }

    public function testModifyInvestigatorFormShouldFailNotExistingForm()
    {
        Review::where('visit_id', $this->visit->id)->where('study_name', $this->studyName)->delete();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->put('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(404);
    }
}

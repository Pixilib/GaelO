<?php

namespace Tests\Feature\TestInvestigatorForm;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CreateInvestigatorFormTest extends TestCase
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
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $this->studyName = $study->name;
        $this->centerCode = $patient->center_code;
        $this->visit = Visit::Factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
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

    public function testCreateInvestigatorForm()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(201);
    }


    public function testCreateInvestigatorFormShouldFailMissingConstraints()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => [
                ...$this->validFormPayload,
                'infection' => null
            ],
            'validated' => true
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(400);
    }

    public function testCreateInvestigatorFormAsIncompleteDraft()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $payload = [
            'data' => [
                ...$this->validFormPayload,
                'infection' => null
            ],
            'validated' => false
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(201);
    }



    public function testCreateInvestigatorFormShouldFailNoRole()
    {

        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }

    public function testCreateInvestigatorFormShouldFailInvestigatorFormNotNeeded()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_NOT_NEEDED;
        $this->visit->save();

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }

    public function testCreateInvestigatorFormShouldFailInvestigatorFormAlreadyExisting()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->centerCode);

        //Set investigator form status to draft to simulate an existing form
        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_DRAFT;
        $this->visit->save();

        $payload = [
            'data' => $this->validFormPayload,
            'validated' => true
        ];

        $this->post('api/visits/' . $this->visit->id . '/investigator-form', $payload)->assertStatus(403);
    }
}

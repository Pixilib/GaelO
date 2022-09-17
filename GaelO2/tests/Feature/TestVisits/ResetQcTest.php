<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\ReviewStatus;
use App\Models\VisitGroup;
use Tests\AuthorizationTools;

class ResetQcTest extends TestCase
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

        $this->patient = Patient::factory()->create();
        $this->visitGroup = VisitGroup::factory()->studyName($this->patient->study_name)->create();

        $this->visit = Visit::factory()
        ->forVisitType([
            'visit_group_id'=>$this->visitGroup->id,
            'local_form_needed' => true,
            'review_probability' => 100
        ])
        ->patientId($this->patient->id)
        ->uploadDone()
        ->stateQualityControl(Constants::QUALITY_CONTROL_NOT_DONE)
        ->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)
        ->create();

        $this->studyName = $this->visit->patient->study_name;

        $this->reviewStatus = ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->studyName)->create();


    }

    public function testResetQc()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'reason' => 'error filling qc'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName, $payload);

        $response->assertStatus(200);

    }

    public function testResetQcMissingReason()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName, $payload);

        $response->assertStatus(400);

    }

    public function testResetQcShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'reason' => 'error filling qc'
        ];
        $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName, $payload)->assertStatus(403);

    }

    public function testResetQcShouldFailReviewStatusStarted()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);


        $this->reviewStatus->review_status = Constants::REVIEW_STATUS_ONGOING;
        $this->reviewStatus->save();

        $payload = [
            'reason' => 'error filling qc'
        ];
        $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName, $payload)->assertStatus(400);

    }
}

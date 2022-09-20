<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CorrectiveActionTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $visitType = VisitType::factory()->localFormNeeded()->create();

        $this->visit = Visit::factory()
            ->visitTypeId($visitType->id)
            ->uploadDone()
            ->stateQualityControl(Constants::QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED)
            ->stateInvestigatorForm(Constants::INVESTIGATOR_FORM_DONE)->create();

        $this->studyName = $this->visit->patient->study_name;
        $centerCode = $this->visit->patient->center_code;

        $this->currentUserId = AuthorizationTools::actAsAdmin(false);
        $userEntity = User::find($this->currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();
    }


    public function testCorrectiveAction()
    {
        AuthorizationTools::addRoleToUser($this->currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone' => true,
            'comment' => "lala"
        ];
        $response = $this->patch('/api/visits/' . $this->visit->id . '/corrective-action?studyName=' . $this->studyName, $payload);
        $response->assertStatus(200);
    }

    public function testCorrectiveActionShouldFailWrongStudy()
    {
        AuthorizationTools::addRoleToUser($this->currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);

        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone' => true,
            'comment' => "lala"
        ];
        $response = $this->patch('/api/visits/' . $this->visit->id . '/corrective-action?studyName=' . $this->studyName.'wrong', $payload);
        $response->assertStatus(403);
    }

    public function testCorrectiveActionShouldFailNoRole()
    {
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone' => true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/corrective-action?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testCorrectiveActionShouldFailCorrectiveActionNotAsked()
    {
        AuthorizationTools::addRoleToUser($this->currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $this->visit->state_quality_control = Constants::QUALITY_CONTROL_NOT_DONE;
        $this->visit->save();
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone' => true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/corrective-action?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testCorrectiveActionShouldFailFormMissing()
    {
        AuthorizationTools::addRoleToUser($this->currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $this->visit->state_investigator_form = Constants::INVESTIGATOR_FORM_NOT_DONE;
        $this->visit->save();
        $payload = [
            'newSeriesUploaded' => true,
            'newInvestigatorForm' => true,
            'correctiveActionDone' => true,
            'comment' => "lala"
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/corrective-action?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\UploadStatusEnum;
use App\Models\Patient;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\VisitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class QcTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->patient = Patient::factory()->create();
        $this->visitGroup = VisitGroup::factory()->studyName($this->patient->study_name)->create();

        $this->visit = Visit::factory()
            ->forVisitType([
                'visit_group_id' => $this->visitGroup->id,
                'local_form_needed' => true,
                'review_probability' => 100
            ])
            ->patientId($this->patient->id)
            ->uploadDone()
            ->stateQualityControl(QualityControlStateEnum::NOT_DONE->value)
            ->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)
            ->create();

        $this->studyName = $this->visit->patient->study_name;

        $this->reviewStatus = ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->studyName)->create();
    }

    public function testQc()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => true,
            'formQc' => true
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(200);

        $reviewStatus = ReviewStatus::where('visit_id', $this->visit->id)->where('study_name', $this->studyName)->sole();
        $this->assertEquals(1, $reviewStatus->review_available);
    }

    public function testQcForbiddenNotRole()
    {

        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => true,
            'formQc' => true,
            'imageQcComment' => 'OK',
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testQcForbiddenNotUploaded()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $this->visit->upload_status = UploadStatusEnum::NOT_DONE->value;
        $this->visit->save();

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => true,
            'formQc' => true,
            'imageQcComment' => 'OK',
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testQcForbiddenQcAlreadyDone()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $this->visit->state_quality_control = QualityControlStateEnum::REFUSED->value;
        $this->visit->save();

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => true,
            'formQc' => true,
            'imageQcComment' => 'OK',
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(403);
    }

    public function testQcCorrectiveActionUnlockLocalForm()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);


        $review = Review::factory()
            ->validated()
            ->visitId($this->visit->id)
            ->studyName($this->studyName)
            ->create();

        $payload = [
            'stateQc' => QualityControlStateEnum::CORRECTIVE_ACTION_ASKED->value,
            'imageQc' => true,
            'formQc' => false,
            'imageQcComment' => 'OK',
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(200);
    }

    public function testQcAcceptedWithNoAcceptedItemShouldFail()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => false,
            'formQc' => false,
            'imageQcComment' => 'OK',
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(400);
    }

    public function testQCImageRefusedReasonShouldBeSpecified()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => false,
            'formQc' => true,
            'formQcComment' => 'non'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(400);
    }

    public function testQCFormRefusedReasonShouldBeSpecified()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'stateQc' => QualityControlStateEnum::ACCEPTED->value,
            'imageQc' => true,
            'formQc' => false,
            'imageQcComment' => 'OK'
        ];

        $response = $this->patch('/api/visits/' . $this->visit->id . '/quality-control?studyName=' . $this->studyName, $payload);
        $response->assertStatus(400);
    }

    public function testAskResetQc()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'message' => 'sent wrong qc'
        ];

        $response = $this->post('/api/visits/' . $this->visit->id . '/quality-control/unlock', $payload);

        $response->assertStatus(200);
    }

    public function testAskResetQcShouldFailNoRole()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'message' => 'sent wrong qc'
        ];

        $response = $this->post('/api/visits/' . $this->visit->id . '/quality-control/unlock', $payload);

        $response->assertStatus(403);
    }

    public function testAskResetQcShoudFailNoMessage()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_CONTROLLER, $this->studyName);

        $payload = [
            'message' => ''
        ];

        $response = $this->post('/api/visits/' . $this->visit->id . '/quality-control/unlock', $payload);

        $response->assertStatus(400);
    }
}

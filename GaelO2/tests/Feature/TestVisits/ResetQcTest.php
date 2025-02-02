<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\Patient;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\ReviewStatus;
use App\Models\VisitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class ResetQcTest extends TestCase
{
    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private Patient $patient;
    private VisitGroup $visitGroup;
    private Visit $visit;
    private ReviewStatus $reviewStatus;
    private string $studyName;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
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
        ->stateQualityControl(QualityControlStateEnum::NOT_DONE->value)
        ->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)
        ->create();

        $this->studyName = $this->visit->patient->study_name;
        $this->reviewStatus = ReviewStatus::factory()->visitId($this->visit->id)->studyName($this->studyName)->create();
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
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

        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_RESET_QC, Mockery::any());
    }

    public function testResetQcShouldFailWrongStudy()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $payload = [
            'reason' => 'error filling qc'
        ];

        $response = $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName.'wrong', $payload);

        $response->assertStatus(403);

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


        $this->reviewStatus->review_status = ReviewStatusEnum::ONGOING->value;
        $this->reviewStatus->save();

        $payload = [
            'reason' => 'error filling qc'
        ];
        $this->patch('/api/visits/'.$this->visit->id.'/quality-control/reset?studyName='.$this->studyName, $payload)->assertStatus(400);

    }
}

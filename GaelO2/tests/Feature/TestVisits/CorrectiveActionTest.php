<?php

namespace Tests\Feature\TestVisits;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CorrectiveActionTest extends TestCase
{

    use RefreshDatabase;
    private MockInterface $trackerSpy;
    private string $studyName;
    private Visit $visit;
    private int $currentUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $visitType = VisitType::factory()->localFormNeeded()->create();

        $this->visit = Visit::factory()
            ->visitTypeId($visitType->id)
            ->uploadDone()
            ->stateQualityControl(QualityControlStateEnum::CORRECTIVE_ACTION_ASKED->value)
            ->stateInvestigatorForm(InvestigatorFormStateEnum::DONE->value)->create();

        $this->studyName = $this->visit->patient->study_name;
        $centerCode = $this->visit->patient->center_code;

        $this->currentUserId = AuthorizationTools::actAsAdmin(false);
        $userEntity = User::find($this->currentUserId);
        $userEntity->center_code = $centerCode;
        $userEntity->save();
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($this->currentUserId, Constants::ROLE_INVESTIGATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_CORRECTIVE_ACTION, Mockery::any());
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
        $this->visit->state_quality_control = QualityControlStateEnum::NOT_DONE->value;
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
        $this->visit->state_investigator_form = InvestigatorFormStateEnum::NOT_DONE->value;
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

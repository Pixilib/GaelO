<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\MailServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use App\Models\Study;
use App\Models\VisitGroup;
use App\Models\VisitType;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\ReviewStatus;

class VisitServiceTest extends TestCase
{
    use RefreshDatabase;

    /*
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Passport::actingAs(
            User::where('id', 1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'code' => 1234]);
        $this->patient = factory(Patient::class)->create(['code' => '12341234123412', 'study_name' => 'test', 'center_code' => 0]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);

        $this->mailServiceSpy = $this->spy(MailServices::class);
        $this->visitService  = App::Make(\App\GaelO\Services\VisitService::class);
    }

    private function createVisit(string $stateInvestigatorForm, int $qcProbability, bool $localFormNeeded, int $reviewProbability)
    {


        $this->visitType = factory(VisitType::class)->create(
            [
                'visit_group_id' => $this->visitGroup['id'],
                'local_form_needed' => $localFormNeeded,
                'qc_probability' => $qcProbability,
                'review_probability' => $reviewProbability
            ]
        );

        $this->visit = factory(Visit::class)->create(
            [
                'creator_user_id' => 1,
                'patient_id' => $this->patient['id'],
                'visit_type_id' => $this->visitType['id'],
                'status_done' => 'Done',
                'state_investigator_form'=> $stateInvestigatorForm
            ]
        );

        $this->reviewStatus = factory(ReviewStatus::class)->create(
            [
                'visit_id' => $this->visit['id'],
                'study_name' => $this->study['name'],
                'review_available' => 0
            ]
        );


    }


    public function testUpdateUploadStatusQC1InvestForm1Review1()
    {
        $this->createVisit(Constants::INVESTIGATOR_FORM_DONE, true, true, true);
        $this->visitService->updateUploadStatus($this->visit['id'], 'Done');
    }

    public function testUpdateUploadStatusQC1InvestForm0Review1()
    {
        $this->createVisit(Constants::INVESTIGATOR_FORM_DONE, true, false, true);

        $this->visitService->updateUploadStatus($this->visit['id'], 'Done');
    }

    public function testUpdateUploadStatusQC0InvestForm0Review1()
    {
        $this->createVisit(Constants::INVESTIGATOR_FORM_DONE, false, false, true);

        $this->visitService->updateUploadStatus($this->visit['id'], 'Done');

        $this->mailServiceSpy->shouldHaveReceived('sendReviewReadyMessage')->once();
    }

    public function testUpdateUploadStatusInvestigatorFormNotDone()
    {
        $this->createVisit(Constants::INVESTIGATOR_FORM_DRAFT, false, false, true);

        $this->visitService->updateUploadStatus($this->visit['id'], 'Done');
    }
    */

}


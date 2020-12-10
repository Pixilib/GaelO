<?php

namespace Tests\Unit;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\MailServices;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\VisitGroup;
use App\VisitType;
use App\Patient;
use App\Visit;
use App\ReviewStatus;

class VisitServiceTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id', 1)->first()
        );

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);

        $this->mailServiceSpy = $this->spy(MailServices::class);
        $this->visitService  = App::Make(\App\GaelO\Services\VisitService::class);
    }

    private function createVisit(string $stateInvestigatorForm, bool $qcNeeded, bool $localFormNeeded, bool $reviewNeeded)
    {


        $this->visitType = factory(VisitType::class)->create(
            [
                'visit_group_id' => $this->visitGroup['id'],
                'local_form_needed' => $localFormNeeded,
                'qc_needed' => $qcNeeded,
                'review_needed' => $reviewNeeded
            ]
        );

        $this->visit = factory(Visit::class)->create(
            [
                'creator_user_id' => 1,
                'patient_code' => $this->patient['code'],
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

        $this->mailServiceSpy->shouldHaveReceived('sendAvailableReviewMessage')->once();
    }

    public function testUpdateUploadStatusInvestigatorFormNotDone()
    {
        $this->createVisit(Constants::INVESTIGATOR_FORM_DRAFT, false, false, true);

        $this->visitService->updateUploadStatus($this->visit['id'], 'Done');
    }

}

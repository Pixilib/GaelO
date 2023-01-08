<?php

namespace Tests\Feature\TestExportService;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\ExportStudyService;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Role;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;
use App\Models\Tracker;
use App\Models\VisitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;


class ExportStudyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExportStudyService $exportStudyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->exportStudyService = App::make(ExportStudyService::class);

        $this->markTestSkipped();
    }

    public function testExportUser()
    {
        $study = Study::factory()->create();
        Role::factory()->studyName($study->name)->roleName(Constants::ROLE_INVESTIGATOR)->create();
        $this->exportStudyService->setStudyName($study->name);
        $this->exportStudyService->exportUsersOfStudy();
    }

    public function testExportPatient()
    {
        $study = Study::factory()->create();
        Patient::factory()->studyName($study->name)->count(50)->create();
        $this->exportStudyService->setStudyName($study->name);
        $this->exportStudyService->exportPatientTable();
    }

    public function testExportVisit(){
        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study_name;
        $visits = Visit::factory()->visitTypeId($visitType->id)->count(10)->create();

        $visits->each(function($visit) use ($studyName) {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });
        $this->exportStudyService->setStudyName($studyName);
        $this->exportStudyService->exportVisitTable();
    }

    public function testExportDicom(){
        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study_name;
        $visits = Visit::factory()->visitTypeId($visitType->id)->count(10)->create();

        $visits->each(function($visit) {
            $dicomStudy = DicomStudy::factory()->visitId($visit->id)->create();
            DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->create();
        });

        $this->exportStudyService->setStudyName($studyName);
        $this->exportStudyService->exportDicomsTable();

    }

    public function testExportReview(){

        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->name('FDG')->studyName($study->name)->create();
        $visitType = VisitType::factory()->name('PET_0')->visitGroupId($visitGroup->id)->create();
        $studyName = $study->name;
        $patient = Patient::factory()->studyName($studyName)->create();

        $visits = Visit::factory()->visitTypeId($visitType->id)->patientId($patient->id)->count(3)->create();

        $visits->each(function($visit) use ($studyName) {
            Review::factory()->visitId($visit->id)->studyName($studyName)->count(5)->create();
            Review::factory()->visitId($visit->id)->studyName($studyName)->reviewForm()->count(5)->create();
        });

        $this->exportStudyService->setStudyName($studyName);
        $this->exportStudyService->exportReviewerForms();

    }

    public function testExportInvestigatorForm(){

        $study = Study::factory()->name('TEST')->create();
        $visitGroup = VisitGroup::factory()->name('FDG')->studyName($study->name)->create();
        $visitType = VisitType::factory()->name('PET_0')->visitGroupId($visitGroup->id)->create();
        $studyName = $study->name;
        $patient = Patient::factory()->studyName($studyName)->create();
        $visits = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->count(3)->create();

        $visits->each(function($visit) use ($studyName) {
            Review::factory()->visitId($visit->id)->studyName($studyName)->count(5)->create();
            Review::factory()->visitId($visit->id)->studyName($studyName)->reviewForm()->count(5)->create();
        });

        $this->exportStudyService->setStudyName($studyName);
        $this->exportStudyService->exportInvestigatorForms();

    }

    public function testExportTracker(){

        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study_name;

        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_INVESTIGATOR)->create();
        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_SUPERVISOR)->create();

        $this->exportStudyService->setStudyName($studyName);
        $this->exportStudyService->exportTrackerTable();

    }
}

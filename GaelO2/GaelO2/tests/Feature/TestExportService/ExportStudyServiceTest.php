<?php

namespace Tests\Feature\TestExportService;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\ExportStudyService;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;
use App\Models\Tracker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;
use Tests\TestCase;


class ExportStudyServiceTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

    }

    private ExportStudyService $exportServiceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportServiceData = App::make(ExportStudyService::class);
    }

    public function testExportPatient()
    {
        $study = Study::factory()->create();
        Patient::factory()->studyName($study->name)->count(50)->create();
        $this->exportServiceData->setStudyName($study->name);
        $this->exportServiceData->exportPatientTable();
    }

    public function testExportVisit(){
        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study->name;
        $visits = Visit::factory()->visitTypeId($visitType->id)->count(10)->create();

        $visits->each(function($visit) use ($studyName) {
            ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();
        });
        $this->exportServiceData->setStudyName($studyName);
        $this->exportServiceData->exportVisitTable();
    }

    public function testExportDicom(){
        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study->name;
        $visits = Visit::factory()->visitTypeId($visitType->id)->count(10)->create();

        $visits->each(function($visit) {
            $dicomStudy = DicomStudy::factory()->visitId($visit->id)->create();
            DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->create();
        });

        $this->exportServiceData->setStudyName($studyName);
        $this->exportServiceData->exportDicomsTable();

    }

    public function testExportReview(){

        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study->name;
        $visits = Visit::factory()->visitTypeId($visitType->id)->count(3)->create();

        $visits->each(function($visit) use ($studyName) {
            Review::factory()->visitId($visit->id)->studyName($studyName)->count(5)->create();
            Review::factory()->visitId($visit->id)->studyName($studyName)->reviewForm()->count(5)->create();
        });

        $this->exportServiceData->setStudyName($studyName);
        $this->exportServiceData->exportReviewTable();

    }

    public function testExportTracker(){

        $visitType = VisitType::factory()->create();
        $studyName = $visitType->visitGroup->study->name;

        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_INVESTIGATOR)->create();
        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_SUPERVISOR)->create();

        $this->exportServiceData->setStudyName($studyName);
        $this->exportServiceData->exportTrackerTable();

    }
}

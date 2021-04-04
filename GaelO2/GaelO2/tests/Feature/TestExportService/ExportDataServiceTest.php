<?php

namespace Tests\Feature\TestExportService;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\ExportDataService;
use App\GaelO\Services\VisitTreeService;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

class ExportDataServiceTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

    }

    private ExportDataService $exportServiceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportServiceData = App::make(ExportDataService::class);
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
}

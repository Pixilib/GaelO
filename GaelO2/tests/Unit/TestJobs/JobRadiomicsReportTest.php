<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\JobRadiomicsReport;
use App\Mail\JobFailure;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Study;
use App\Models\Visit;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JobRadiomicsReportTest extends TestCase
{
    use RefreshDatabase;
    
    private Study $study;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->study = Study::factory()->name("TEST")->create();
        $this->visit = Visit::factory()->create();
        $dicomStudy = DicomStudy::factory()->visitId($this->visit->id)->create();
        $dicomSeries = DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->count(5)->create();
      
        
    }

    public function testTmtvInference() {
        $this->markTestSkipped();
        JobRadiomicsReport::dispatchSync($this->visit->id);
    }

    public function testTmtvInferenceFailMethods(){
        Mail::fake();
        $radiomicsReportJob = new JobRadiomicsReport($this->visit->id, 1);
        $exception = new Exception('fakeError');
        $radiomicsReportJob->failed($exception);
        Mail::assertQueued(JobFailure::class);
    }
}

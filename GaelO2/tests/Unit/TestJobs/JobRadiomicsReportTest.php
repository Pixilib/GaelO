<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\JobRadiomicsReport;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRadiomicsReportTest extends TestCase
{
    use RefreshDatabase;
    
    private Study $study;

    protected function setUp(): void
    {
        $this->markTestSkipped();
        parent::setUp();
        $this->artisan('db:seed');
        $this->study = Study::factory()->name("TEST")->create();
        
    }

    public function testTmtvInference() {
        JobRadiomicsReport::dispatchSync(5);
    }
}

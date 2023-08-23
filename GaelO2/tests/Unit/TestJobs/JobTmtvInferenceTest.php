<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\JobTmtvInference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTmtvInferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testTmtvInference() {
        JobTmtvInference::dispatchSync();
    }
}

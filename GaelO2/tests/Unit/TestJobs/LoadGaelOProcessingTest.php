<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Services\GaelOProcessingService\AzureService;
use App\Jobs\LoadGaelOProcessing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoadGaelOProcessingTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->markTestSkipped();
        $this->azureService = App::make(AzureService::class);
    }

    public function  testLoad()
    {
        $load = new LoadGaelOProcessing(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"], 'Nimportequoi');
        $res = $load->handle($this->azureService);
        Log::info($res);
    }
}

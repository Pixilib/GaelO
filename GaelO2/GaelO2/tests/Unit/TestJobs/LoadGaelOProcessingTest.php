<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\LoadGaelOProcessing;
use App\GaelO\Services\AzureService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoadGaelOProcessingTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
        $this->markTestSkipped();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->azureService = App::make(AzureService::class);
    }

    public function  testLoad()
    {
        $load = new LoadGaelOProcessing(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"], 'Nimportequoi');
        $res = $load->handle($this->azureService);
        Log::info($res);
    }
}

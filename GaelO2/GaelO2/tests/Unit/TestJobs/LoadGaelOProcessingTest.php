<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\LoadGaelOProcessing;
use App\GaelO\Services\AzureService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoadGaelOProcessingTest extends TestCase{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

protected function setUp():void {

    parent::setUp();

    $this ->azureService = App::make(AzureService::class);
}

public function  testLoad (){

    Log::info("je suis dans le test");
    $Load =new LoadGaelOProcessing(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"],'Nimportequoi');
    $res = $Load -> handle($this->azureService);
    Log::info($res);
    
    }

}
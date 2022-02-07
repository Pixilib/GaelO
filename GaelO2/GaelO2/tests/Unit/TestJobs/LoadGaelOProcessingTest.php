<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\LoadGaelOProcessing;
use App\Gaelo\Services\AzureService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
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
    $Load =new LoadGaelOProcessing(["3a84b7f7-d0c66087-d70b292e-0c585356-56b6ccb3"],'Nimportequoi');
    $res = $Load -> handle($this->azureService);
    Log::info($res);
    
    }

}
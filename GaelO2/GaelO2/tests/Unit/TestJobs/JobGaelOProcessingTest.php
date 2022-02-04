<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Services\GaelOProcessingService;
use App\Jobs\LoadGaelOProcessing;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;

class JobGaelOProcessingTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }


    public function testQueue(){

        
        //$new_job = new JobGaelOProcessing(["3a84b7f7-d0c66087-d70b292e-0c585356-56b6ccb3"],'Nimportequoi','http://gaeloprocessing:8000');
       
        //dispatch_sync($new_job);
    }

    public function testBatch(){

        //Bus::fake();
        Bus::fake();

        $job1 = new JobGaelOProcessing( ["3a84b7f7-d0c66087-d70b292e-0c585356-56b6ccb3"],'Nimportequoi','http://gaeloprocessing:8000');
        $job2 = new JobGaelOProcessing( ["3a84b7f7-d0c66087-d70b292e-0c585356-56b6ccb3"],'Nimportequoi','http://gaeloprocessing:8000');

        $batch = Bus::batch([
            $job1,
            $job2
        ])->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->name('processing')->allowFailures()->dispatch();

        //dd($batch);

        Bus::assertBatched(function (PendingBatch $batch) {
            
        
            // Make sure you test the batch is dispatched
            return $batch->name === 'processing';
        });
        $this->assertEquals(2, $batch->totalJobs);

        $gaeloProcessing = App::make(GaelOProcessingService::class);
        //dd($job2);
        $job2->handle($gaeloProcessing);
        $job1->handle($gaeloProcessing);
        dd($batch->finished());
        // [...] Run code that dispatches the job
        //JobGaelOProcessing::dispatch( ["3a84b7f7-d0c66087-d70b292e-0c585356-56b6ccb3"],'Nimportequoi','http://gaeloprocessing:8000');

        

       

    }
}



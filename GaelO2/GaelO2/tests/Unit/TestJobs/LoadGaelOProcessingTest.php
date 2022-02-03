<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\LoadGaelOProcessing;
use Tests\TestCase;

use Illuminate\Contracts\Queue\ShouldQueue;

class LoadGaelOProcessingTest extends TestCase
{
    protected function setUp():void {

        parent::setUp();

        $this ->loadGaelOProcessingTest = App::make(LoadGaeloProcessing::class);
    }

    public testQueue(){

        use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShouldBeUnique;

    $new_job = new JobGaelOProcessing("717b834e-a4e89074-51018c12-59e12ebd-598a673f",'Nimportequoi',$ip);
    $new_job->dispatch();
    
    Queue::assertPushed(JobGaelOProcessing::class, function(){
        // check some job property against the expected
    });
    
    $new_job->handle(App::make(LoadGaeloProcessing::class));
    
    Queue::assertPushed(JobGaelOProcessing::class, function(){
        // check some job property against the expected
    });
}
}



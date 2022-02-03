<?php

namespace Tests\Unit\TestJobs;

use App\Jobs\LoadGaelOProcessing;
use App\Jobs\JobGaelOProcessing;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;


class JobGaelOProcessingTest extends TestCase
{
    protected function setUp():void {

        parent::setUp();

    }

    public function testQueue(){


    $new_job = new JobGaelOProcessing("717b834e-a4e89074-51018c12-59e12ebd-598a673f",'Nimportequoi','
    20.74.32.229
    ');
    Log::info(json_encode($new_job));
    $new_job->dispatch("717b834e-a4e89074-51018c12-59e12ebd-598a673f",'Nimportequoi','
    20.74.32.229
    ');
    
    Queue::assertPushed(JobGaelOProcessing::class, function($new_job){
        
        return $new_job->class ===JobGaelOProcessing::class;
    });
    
    $new_job->handle();
    Log::info(json_encode($new_job));
    Queue::assertPushed(JobGaelOProcessing::class, function($new_job){
        
        return $new_job->class ===JobGaelOProcessing::class;
    });
}
}



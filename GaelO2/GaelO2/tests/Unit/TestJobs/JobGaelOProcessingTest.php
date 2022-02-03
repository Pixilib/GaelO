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

        
        $new_job = new JobGaelOProcessing(["a97f5e66-bbff00d4-1639c63f-a3e1e53a-d4b5e553"],'Nimportequoi','http://gaeloprocessing:8000');
       
        dispatch_sync($new_job);
    }
}



<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use App\Jobs\JobAutoQc;
use App\Jobs\JobGaelOProcessing;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Database\Factories\UserFactory;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Mockery;

class JobAutoQcTest extends TestCase
{
   

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testSendAutoQc(){
        $visit = Visit::factory()->create();
        $dicomStudy = DicomStudy::factory()->visitId($visit->id)->create();
        $dicomSeries = DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->count(5)->create();

        $studyName = $visit->patient->study_name;

        $user = User::factory()->create();
        Role::factory()->userId($user->id)->studyName($studyName)->roleName(Constants::ROLE_CONTROLLER)->create();

        $strJsonFileContents = file_get_contents(getcwd()."/tests/Unit/TestJobs/sharedTags.json");
        $sharedTags = json_decode($strJsonFileContents, true);
        $mockOrthancService = Mockery::mock(OrthancService::class);
        $mockOrthancService->shouldReceive('getSharedTags')
            ->andReturn($sharedTags);
        
        $mockOrthancService->ShouldReceive('getMIP')
            ->andReturn((getcwd()."/tests/Unit/TestJobs/test.gif"));
        
        app()->instance(OrthancService::class, $mockOrthancService);
        JobAutoQc::dispatchSync($visit->id);

    }
}



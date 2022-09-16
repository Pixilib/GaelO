<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use App\Jobs\JobAutoQc;
use App\Jobs\JobGaelOProcessing;
use App\Models\DicomSeries;
use App\Models\DicomStudy;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Enum\JobAutoQc as EnumJobAutoQc;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function testSendAutoQc() {
        $visit = Visit::factory()->create();
        $dicomStudy = DicomStudy::factory()->visitId($visit->id)->create();
        $dicomSeries = DicomSeries::factory()->studyInstanceUID($dicomStudy->study_uid)->count(5)->create();
        Review::factory()->visitId($visit->id)->validated()->create();
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
        $mockOrthancService->ShouldReceive('getMosaic')
            ->andReturn((getcwd()."/tests/Unit/TestJobs/testMosaic.gif"));
        app()->instance(OrthancService::class, $mockOrthancService);

        $investigatorInfos = [
            '0008,0012' => [
                'Name' => 'StudyDate',
                'Value' => '2021-01-01',
            ],
        ];
        $mockReviewRepository = Mockery::mock(ReviewRepository::class);
        $mockReviewRepository->shouldReceive('getInvestigatorForm')
            ->andReturn($investigatorInfos);

        JobAutoQc::dispatchSync($visit->id);
    }
}

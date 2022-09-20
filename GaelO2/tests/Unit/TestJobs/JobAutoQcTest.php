<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\StoreObjects\OrthancMetaData;
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
use Mockery\MockInterface;
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
        $tags = new OrthancMetaData($sharedTags);
        $strJsonFileContentsData = file_get_contents(getcwd()."/tests/Unit/TestJobs/seriesData.json");
        $decoded = json_decode($strJsonFileContentsData, true);

        $mockOrthancService = $this->partialMock(OrthancService::class, function (MockInterface $mock) use ($tags, $decoded){
            $mock->shouldReceive('getMetaData')->andReturn($tags);
            $mock->shouldReceive('setOrthancServer')->andReturn(null);
            $mock->shouldReceive('getOrthancRessourcesDetails')->andReturn($decoded);
            $mock->shouldReceive('getSeriesMIP')->andReturn((getcwd()."/tests/Unit/TestJobs/testGif.gif"));
            $mock->shouldReceive('getSeriesMosaic')->andReturn((getcwd()."/tests/Unit/TestJobs/testMosaic.gif"));
        });

        $investigatorInfos = [
            '0008,0012' => [
                'Name' => 'StudyDate',
                'Value' => '2021-01-01',
            ],
        ];
        $mockReviewRepository = $this->partialMock(ReviewRepository::class, function (MockInterface $mock) use ($investigatorInfos) {
            $mock->shouldReceive('getInvestigatorForm')->andReturn($investigatorInfos);
         });

        app()->instance(OrthancService::class, $mockOrthancService);
        app()->instance(ReviewRepository::class, $mockReviewRepository);
        JobAutoQc::dispatchSync($visit->id);
    }
}
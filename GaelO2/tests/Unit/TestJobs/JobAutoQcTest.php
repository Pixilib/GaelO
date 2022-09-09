<?php

namespace Tests\Unit\TestJobs;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\MailServices;
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
use App\GaelO\Services\StoreObjects\SharedTags;
use Mockery;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

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
        $mailServices = Mockery::mock(MailServices::class);
        $frameworkInterface = Mockery::mock(FrameworkInterface::class);
        $visitRepositoryInterface = Mockery::mock(VisitRepositoryInterface::class);
        $dicomStudyRepositoryInterface = Mockery::mock(DicomStudyRepositoryInterface::class);
        $userRepositoryInterface = Mockery::mock(UserRepositoryInterface::class);
    
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

        $studyInfo = [];
        $studyInfo['studyDescription'] = 'studyDescription';
        $studyInfo['studyManufacturer'] = 'studyManufacturer';
        $studyInfo['studyDate'] = 'studyDate';
        $studyInfo['studyTime'] = 'studyTime';
        $studyInfo['numberOfSeries'] = count($dicomSeries);
        $studyInfo['numberOfInstances'] = 0;
        $seriesInfo = [];
        foreach ($dicomSeries as $series) {
            $studyInfo['numberOfInstances'] += $series['number_of_instances'];
            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesData['image_path'] = (getcwd()."/tests/Unit/TestJobs/test.gif");
            $seriesData['series_description'] = 'seriesDescription';
            $seriesData['infos']['modality'] = 'modality';
            $seriesData['infos']['series_date'] =  'seriesDate';
            $seriesData['infos']['series_time'] = 'seriesTime';
            $seriesData['infos']['slice_thickness'] = 'sliceThickness';
            $seriesData['infos']['pixel_spacing'] = 'pixelSpacing';
            $seriesData['infos']['fov'] = 'fov';
            $seriesData['infos']['matrix_size'] = 'matrixSize';
            $seriesData['infos']['patient_position'] = 'patientPosition';
            $seriesData['infos']['patient_orientation'] = 'patientOrientation';
            $seriesData['infos']['number_of_instances'] = 'numberOfInstances';
            $seriesInfo[] = $seriesData;
        }
        $studyName = 'studyName';
        $visitId = $visit->id;
        $visitType = 'visitType';
        $patientCode = 'patientCode';
        $redirectLink = 'redirectLink';
        $magicLink = 'magicLink';
    }
}



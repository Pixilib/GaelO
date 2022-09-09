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
use App\GaelO\Services\StoreObjects\SharedTags;
use Mockery;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Repositories\DicomStudyRepositoryInterface;
use App\GaelO\Interfaces\Repositories\UserRepositoryInterface;
use App\GaelO\Interfaces\Repositories\VisitRepositoryInterface;

class JobAutoQcTestMail extends TestCase
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

    public function testSendAutoQcMail() {
        $visitRepositoryInterface = Mockery::mock(VisitRepositoryInterface::class);
        $dicomStudyRepositoryInterface = Mockery::mock(DicomStudyRepositoryInterface::class);
        $userRepositoryInterface = Mockery::mock(UserRepositoryInterface::class);
        $frameworkInterface = Mockery::mock(FrameworkInterface::class);
        $sharedTags = Mockery::mock(SharedTags::class);
        $orthancService = Mockery::mock(OrthancService::class);
        $mailServices = Mockery::mock(MailServices::class);

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

        $visitEntity = $visitRepositoryInterface->getVisitContext($this->visitId);
        $dicomStudyEntity = $dicomStudyRepositoryInterface->getDicomsDataFromVisit($this->visitId, false, false);
        $sharedTags = new SharedTags($orthancService->getSharedTags($this->visitId));
        $studyInfo = [];
        $studyInfo['studyDescription'] = $sharedTags->getStudyDescription();
        $studyInfo['studyManufacturer'] = $sharedTags->getStudyManufacturer();
        $studyInfo['studyDate'] = $sharedTags->getStudyDate();
        $studyInfo['studyTime'] = $sharedTags->getStudyTime();
        $studyInfo['numberOfSeries'] = count($dicomStudyEntity[0]['dicom_series']);
        $studyInfo['numberOfInstances'] = 0;
        $seriesInfo = [];
        foreach ($dicomStudyEntity[0]['dicom_series'] as $series) {
            $studyInfo['numberOfInstances'] += $series['number_of_instances'];
            $seriesData = [];
            $seriesData['infos'] = [];
            $seriesSharedTags = new SharedTags($orthancService->getSharedTags($series['orthanc_id']));
            $seriesData['image_path'] = $orthancService->getMIP($series['orthanc_id']);
            $seriesData['series_description'] = $seriesSharedTags->getSeriesDescription();
            $seriesData['infos']['modality'] = $seriesSharedTags->getSeriesModality();
            $seriesData['infos']['series_date'] =  $seriesSharedTags->getSeriesDate();
            $seriesData['infos']['series_time'] = $series['acquisition_time'];
            $seriesData['infos']['slice_thickness'] = $seriesSharedTags->getSliceThickness();
            $seriesData['infos']['pixel_spacing'] = $seriesSharedTags->getPixelSpacing();
            $seriesData['infos']['fov'] = $seriesSharedTags->getFieldOfView();
            $seriesData['infos']['matrix_size'] = $seriesSharedTags->getMatrixSize();
            $seriesData['infos']['patient_position'] = $seriesSharedTags->getPatientPosition();
            $seriesData['infos']['patient_orientation'] = $seriesSharedTags->getImageOrientation();
            $seriesData['infos']['number_of_instances'] = $series['number_of_instances'];
            if ($seriesData['infos']['modality'] == 'MR') {
                $seriesData['infos']['scanning_sequence'] = $seriesSharedTags->getScanningSequence();
                $seriesData['infos']['sequence_variant'] = $seriesSharedTags->getSequenceVariant();
                $seriesData['infos']['echo_time'] = $seriesSharedTags->getEchoTime();
                $seriesData['infos']['inversion_time'] = $seriesSharedTags->getInversionTime();
                $seriesData['infos']['echo_train_length'] = $seriesSharedTags->getEchoTrainLength();
                $seriesData['infos']['spacing_between_slices'] = $seriesSharedTags->getSpacingBetweenSlices();
                $seriesData['infos']['protocol_name'] = $seriesSharedTags->getProtocolName();
            } else if ($seriesData['infos']['modality'] == 'PT') {
                $seriesData['infos']['patient_weight'] = $seriesSharedTags->getPatientWeight();
                $seriesData['infos']['patient_height'] = $seriesSharedTags->getPatientHeight();
                $seriesData['infos'][] = [$seriesData['infos'], ...$seriesSharedTags->getRadioPharmaceuticalTags($orthancService,
                    $series['dicom_instances'][0]['orthanc_id'])];
            }
            $seriesInfo[] = $seriesData;
        }
        $studyName = $visitEntity['patient']['study_name'];
        $visitId = $visitEntity['id'];
        $visitType = $visitEntity['visit_type']['name'];
        $patientCode = $visitEntity['patient']['code'];
        $controllerUsers = $userRepositoryInterface->getUsersByRolesInStudy($studyName, Constants::ROLE_CONTROLLER);
        foreach ($controllerUsers as $user) {
            $redirectLink = '/study/'.$studyName.'/role/'.Constants::ROLE_CONTROLLER.'/visit/'.$visitId;
            $magicLink = $frameworkInterface->createMagicLink($user['id'], $redirectLink);
            $mailServices->sendAutoQC($studyName, $visitType, $patientCode, $studyInfo, $seriesInfo, $magicLink, $user['email'], );
        }
    }
    JobAutoQc::dispatchSync($visit->id);
}



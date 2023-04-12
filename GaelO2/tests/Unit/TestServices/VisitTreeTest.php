<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Constants\Enums\ReviewStatusEnum;
use App\GaelO\Entities\StudyEntity;
use App\GaelO\Repositories\CenterRepository;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\StudyRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\TreeService\ControllerTreeService;
use App\GaelO\Services\TreeService\InvestigatorTreeService;
use App\GaelO\Services\TreeService\MonitorTreeService;
use App\GaelO\Services\TreeService\ReviewerTreeService;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

class VisitTreeTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();


        $visitArrayMock = [[
            "id" => 23,
            "creator_user_id" => 24,
            "creation_date" => "2021-01-26 23:23:36",
            "patient_id" => 217735,
            "visit_date" => "2021-01-26 23:23:36",
            "visit_type_id" => 18,
            "status_done" => "Done",
            "reason_for_not_done" => "provident",
            "upload_status" => "Not Done",
            "state_investigator_form" => "Not Done",
            "state_quality_control" => "Not Done",
            "controller_user_id" => null,
            "control_date" => "2021-01-26 23:23:36",
            "image_quality_control" => 0,
            "form_quality_control" => 1,
            "image_quality_comment" => "necessitatibus",
            "form_quality_comment" => "ad",
            "corrective_action_user_id" => null,
            "corrective_action_date" => "2021-01-26 23:23:36",
            "corrective_action_new_upload" => 1,
            "corrective_action_investigator_form" => 1,
            "corrective_action_comment" => "molestias",
            "corrective_action_applied" => 0,
            "deleted_at" => null,
            "created_at" => "2021-01-26T23:23:36.000000Z",
            "updated_at" => "2021-01-26T23:23:36.000000Z",
            "visit_type" => [
                "id" => 18,
                "visit_group_id" => 2,
                "name" => "voluptatibus",
                "order" => 26,
                "local_form_needed" => 0,
                "qc_probability" => 0,
                "review_probability" => 100,
                "optional" => "0",
                "limit_low_days" => "638",
                "limit_up_days" => "56788",
                "anon_profile" => "Default",
                "created_at" => "2021-01-26T23:23:36.000000Z",
                "updated_at" => "2021-01-26T23:23:36.000000Z",
                "dicom_constraints" => [],
                "visit_group" => [
                    "id" => 2,
                    "study_name" => "FTF46YRWEZIZB9R9JPON",
                    "modality" => "PT",
                    "name" => "wb",
                    "created_at" => "2021-01-26T23:23:36.000000Z",
                    "updated_at" => "2021-01-26T23:23:36.000000Z",
                ]
            ]
        ]];

        $mockVisitRepository = Mockery::mock(VisitRepository::class);
        $mockVisitRepository->shouldReceive('getVisitsInStudy')
            ->andReturn($visitArrayMock);
        $mockVisitRepository->shouldReceive('getVisitsInStudyAwaitingControllerAction')
            ->andReturn($visitArrayMock);
        $mockVisitRepository->shouldReceive('getPatientsHavingAtLeastOneAwaitingReviewForUser')
            ->andReturn([32]);

        $mockVisitRepository->shouldReceive('getVisitsFromPatientIdsWithContext')
            ->andReturn($visitArrayMock);

        $arrayMockWithReviewStatus = array_map(function ($visit) {
            $visit['review_status']['review_status'] = ReviewStatusEnum::DONE->value;
            return $visit;
        }, $visitArrayMock);

        $mockVisitRepository
            ->shouldReceive('getReviewVisitHistoryFromPatientIdsWithContextAndReviewStatus')
            ->andReturn($arrayMockWithReviewStatus);

        $mockUserRepository = Mockery::mock(UserRepository::class);
        $mockUserRepository->shouldReceive('getAllUsersCenters')
            ->andReturn(['23']);

        $patientRepository = Mockery::mock(PatientRepository::class);
        $patientArray = [
            [
                'id' => 32,
                'code' => 12332,
                'center_code' => 54,
                'center' => [
                    'code' => 54,
                    'name' => "azc",
                    'country_code' => 'FR'
                ],
                'metadata' => [
                    'tags' => []
                ]
            ],
            [
                'id' => 33,
                'code' => 12333,
                'center_code' => 55,
                'center' => [
                    'code' => 55,
                    'name' => "aze",
                    'country_code' => 'IT'
                ],
                'metadata' => [
                    'tags' => []
                ]
            ],
        ];

        $patientRepository->shouldReceive('getPatientsInStudyInCenters')
            ->andReturn($patientArray);
        $patientRepository->shouldReceive('getPatientsFromIdArray')->andReturn($patientArray);;

        $studyRepository = Mockery::mock(StudyRepository::class);
        $studyRepository->shouldReceive('find')
            ->andReturn(StudyEntity::fillFromDBReponseArray([
                'name' => 'test',
                'code' => 1234,
                'patient_code_length' => 14,
                'contact_email' => 'sa@sa.fr',
                'controller_show_all' => false,
                'monitor_show_all' => false,
                'documentation_mandatory' => false,
                'ancillary_of' => null,
                'deleted_at' => null
            ]));


        $centerRepository = Mockery::mock(CenterRepository::class);
        $centerRepository->shouldReceive('getCentersFromCodeArray')
            ->andReturn([[
                'name' => 'Toulouse',
                'code' => 1234,
                'country_code' => 'FR'
            ]]);


        $this->instance(VisitRepository::class, $mockVisitRepository);
        $this->instance(UserRepository::class, $mockUserRepository);
        $this->instance(PatientRepository::class, $patientRepository);
        $this->instance(StudyRepository::class, $studyRepository);
        $this->instance(CenterRepository::class, $centerRepository);
    }

    private function doAssertionOnContent($treeAnswer, bool $withReviewStatus)
    {
        $expectedArray = [
            'id', 'visitType', 'visitDate',
            'stateInvestigatorForm', 'stateQualityControl', 'uploadStatus', 'statusDone', 'visitTypeId', 'patientId'
        ];

        if ($withReviewStatus) $expectedArray = ['reviewStatus'];

        foreach ($expectedArray as $key) {
            $this->assertArrayHasKey($key, $treeAnswer['visits'][0]);
        }
    }

    public function testTreeMonitor()
    {
        $treeService = new MonitorTreeService(
            App::make(UserRepository::class),
            App::make(StudyRepository::class),
            App::make(PatientRepository::class),
            App::make(VisitRepository::class),
            App::make(CenterRepository::class)
        );

        $treeService->setUserAndStudy(1, 'test');
        $tree = $treeService->buildTree();
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeController()
    {
        $treeService = new ControllerTreeService(
            App::make(UserRepository::class),
            App::make(StudyRepository::class),
            App::make(PatientRepository::class),
            App::make(VisitRepository::class),
            App::make(CenterRepository::class)
        );
        $treeService->setUserAndStudy(1, 'test');
        $tree = $treeService->buildTree();
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeInvestigator()
    {
        $treeService = new InvestigatorTreeService(
            App::make(UserRepository::class),
            App::make(StudyRepository::class),
            App::make(PatientRepository::class),
            App::make(VisitRepository::class),
            App::make(CenterRepository::class)
        );
        $treeService->setUserAndStudy(1, 'test');
        $tree = $treeService->buildTree();
        $this->assertArrayHasKey('patients', $tree);
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeReviewer()
    {
        $treeService = new ReviewerTreeService(
            App::make(UserRepository::class),
            App::make(StudyRepository::class),
            App::make(PatientRepository::class),
            App::make(VisitRepository::class),
            App::make(CenterRepository::class)
        );
        $treeService->setUserAndStudy(1, 'test');
        $tree = $treeService->buildTree();
        $this->doAssertionOnContent($tree, true);
    }
}

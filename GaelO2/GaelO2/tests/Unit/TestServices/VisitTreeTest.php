<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\PatientRepository;
use App\GaelO\Repositories\UserRepository;
use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\VisitTreeService;
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
            "patient_code" => 217735,
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
            "corrective_action_applyed" => 0,
            "last_reminder_upload" => "2021-01-26 23:23:36",
            "deleted_at" => null,
            "created_at" => "2021-01-26T23:23:36.000000Z",
            "updated_at" => "2021-01-26T23:23:36.000000Z",
            "visit_type" => [
                "id" => 18,
                "visit_group_id" => 2,
                "name" => "voluptatibus",
                "order" => 26,
                "local_form_needed" => 0,
                "qc_needed" => 0,
                "review_needed" => "0",
                "optional" => "0",
                "limit_low_days" => "638",
                "limit_up_days" => "56788",
                "anon_profile" => "Default",
                "created_at" => "2021-01-26T23:23:36.000000Z",
                "updated_at" => "2021-01-26T23:23:36.000000Z",
                "visit_group" => [
                    "id" => 2,
                    "study_name" => "FTF46YRWEZIZB9R9JPON",
                    "modality" => "PT",
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

        $mockVisitRepository->shouldReceive('getPatientListVisitsWithContext')
            ->andReturn($visitArrayMock);

        $arrayMockWithReviewStatus = array_map(function($visit){
            $visit['review_status']['review_status'] = Constants::REVIEW_STATUS_DONE;
            return $visit;
        }, $visitArrayMock);

        $mockVisitRepository
        ->shouldReceive('getPatientListVisitWithContextAndReviewStatus')
        ->andReturn($arrayMockWithReviewStatus);

        $mockUserRepository = Mockery::mock(UserRepository::class);
        $mockUserRepository->shouldReceive('getAllUsersCenters')
            ->andReturn(['23']);

        $patientRepository = Mockery::mock(PatientRepository::class);
        $patientRepository->shouldReceive('getPatientsInStudyInCenters')
            ->andReturn([['code' => 32]]);

        $this->instance(VisitRepository::class, $mockVisitRepository);
        $this->instance(UserRepository::class, $mockUserRepository);
        $this->instance(PatientRepository::class, $patientRepository);
        $this->treeService = new VisitTreeService(
            App::make(UserRepository::class),
            App::make(PatientRepository::class),
            App::make(VisitRepository::class),
        );
    }

    private function doAssertionOnContent($treeAnswer, bool $withReviewStatus)
    {
        $expectedArray = [
            'id', 'name', 'order', 'optional', 'modality', 'studyName',
            'stateInvestigatorForm', 'stateQualityControl', 'uploadStatus', 'statusDone', 'visitTypeId', 'visitGroupId', 'patientCode'
        ];

        if($withReviewStatus) $expectedArray = ['reviewStatus'];

        foreach ($expectedArray as $key) {
            $this->assertArrayHasKey($key, $treeAnswer['217735']['PT']['26']);
        }
    }

    public function testTreeMonitor()
    {
        $this->treeService->setUserAndStudy(1, Constants::ROLE_MONITOR, 'test');
        $tree = $this->treeService->buildTree();
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeController()
    {
        $this->treeService->setUserAndStudy(1, Constants::ROLE_CONTROLLER, 'test');
        $tree = $this->treeService->buildTree();
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeInvestigator()
    {
        $this->treeService->setUserAndStudy(1, Constants::ROLE_INVESTIGATOR, 'test');
        $tree = $this->treeService->buildTree();
        $this->doAssertionOnContent($tree, false);
    }

    public function testTreeReviewer()
    {
        $this->treeService->setUserAndStudy(1, Constants::ROLE_REVIEWER, 'test');
        $tree = $this->treeService->buildTree();
        $this->doAssertionOnContent($tree, true);
    }
}

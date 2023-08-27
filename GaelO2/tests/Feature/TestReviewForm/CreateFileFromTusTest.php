<?php

use App\GaelO\Constants\Constants;
use App\GaelO\Services\TusService;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

class CreateFileFromTusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->markTestSkipped('Need Orthanc Container Running');
        parent::setUp();
        $this->artisan('db:seed');
        Storage::fake();

        $mockTusService = $this->partialMock(TusService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFile')
            ->andReturnUsing(function () {
                copy((getcwd() . "/tests/data/MR.zip"), (getcwd() . "/tests/data/MR2.zip"));
                chmod((getcwd() . "/tests/data/MR2.zip"),0777); 
                return (getcwd() . "/tests/data/MR2.zip");
            });
            $mock->shouldReceive('deleteFile')
            ->andReturn(null);
        });

        app()->instance(TusService::class, $mockTusService);
    }

    private function createVisit()
    {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        return [
            'studyName' => $study->name,
            'visitId' => $visit->id,
            'centerCode' => $patient->center_code
        ];
    }

    public function testUploadFileFromTus()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName']);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);

        $payload = [
            'id' => $review->id,
            'key' => '25',
            'tusIds' => ['fakeId']
        ];

        $response = $this->post('api/tools/attach-form-file-from-tus', $payload);
        $response->assertSuccessful();
    }

    public function testUploadFileFromTusDicomUpload()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName']);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);

        $payload = [
            'id' => $review->id,
            'key' => '25',
            'tusIds' => ['fakeId', 'fakeId2'],
            'isDicom' => true,
            'numberOfInstances' => 22
        ];

        $response = $this->post('api/tools/attach-form-file-from-tus', $payload);
        $response->assertSuccessful();
    }

    public function testUploadFileFromTusDicomUploadShouldFailWrongNumberInstances()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName']);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);

        $payload = [
            'id' => $review->id,
            'key' => '25',
            'tusIds' => ['fakeId', 'fakeId2'],
            'numberOfInstances' => 21
        ];

        $response = $this->post('api/tools/attach-form-file-from-tus', $payload);
        $response->assertStatus(400);
    }
}

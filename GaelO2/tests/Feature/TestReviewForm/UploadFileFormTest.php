<?php

namespace Tests\Feature\TestReviewForm;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
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
use Mockery;
use Mockery\MockInterface;

class UploadFileFormTest extends TestCase
{
    use RefreshDatabase;
    private MockInterface $trackerSpy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Storage::fake();
        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
    }

    private function createVisit() {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id,
            'centerCode' => $patient->center_code
        ];
    }

    public function testUploadFile()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName'] );
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $response = $this->post('api/reviews/' . $review->id . '/files/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'text/csv']);
        $response->assertSuccessful();
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_INVESTIGATOR, Mockery::any(), Mockery::any(), Constants::TRACKER_SAVE_INVESTIGATOR_FORM, Mockery::any());
    }

    public function testUploadFileShouldFailNoRole()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $response = $this->post('api/reviews/' . $review->id . '/files/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'text/csv']);
        $response->assertStatus(403);
    }

    public function testUploadFileShouldFailWrongMime()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName'] );
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $currentVisit['studyName'] );
        $response = $this->post('api/reviews/' . $review->id . '/files/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'image/png']);
        $response->assertStatus(400);
    }


}

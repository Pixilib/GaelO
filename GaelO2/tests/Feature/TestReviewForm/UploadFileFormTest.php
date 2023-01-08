<?php

use App\GaelO\Adapters\MimeAdapter;
use App\GaelO\Constants\Constants;
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

class UploadFileFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Storage::fake();
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
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'plain/text']);
        $response->assertSuccessful();
    }

    public function testUploadFileShouldFailNoRole()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'plain/text']);
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
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => 'image/png']);
        $response->assertStatus(400);
    }


}

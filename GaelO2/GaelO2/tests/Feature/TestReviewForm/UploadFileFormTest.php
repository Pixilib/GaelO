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
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UploadFileFormTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createVisit() {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id
        ];
    }

    public function testUploadFile()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->reviewForm()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $currentVisit['studyName'] );
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => MimeAdapter::getMimeFromExtension('csv')]);
        $response->assertSuccessful();
    }

    public function testUploadFileShouldFailNoRole()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->reviewForm()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => MimeAdapter::getMimeFromExtension('csv')]);
        $response->assertStatus(403);
    }

    public function testUploadFileShouldFailWrongMime()
    {
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->reviewForm()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $currentVisit['studyName'] );
        $response = $this->post('api/reviews/' . $review->id . '/file/41', [base64_encode("testFileContent")], ['CONTENT_TYPE' => MimeAdapter::getMimeFromExtension('png')]);
        $response->assertStatus(400);
    }
}

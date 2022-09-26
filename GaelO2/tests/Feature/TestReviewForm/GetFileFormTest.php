<?php

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

class GetFileFormTest extends TestCase
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

        $path = $study->name.'/'.'attached_review_file'.'/'.'review_1_41.csv';
        Storage::put($path, "testcontent");
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id,
            'centerCode' => $patient->center_code
        ];
    }

    public function testGetFileOfForm(){
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName'] );
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $review->sent_files = ['41' => $currentVisit['studyName'].'/'.'attached_review_file'.'/'.'review_1_41.csv'];
        $review->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $currentVisit['studyName'] );
        $response = $this->get('api/reviews/' . $review->id . '/file/41?role=Supervisor');
        $response->assertSuccessful();
    }

    public function testGetFileOfFormShouldFailNoRole(){
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName'] );
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $review->sent_files = ['41' => $currentVisit['studyName'].'/'.'attached_review_file'.'/'.'review_1_41.csv'];
        $review->save();
        $response = $this->get('api/reviews/' . $review->id . '/file/41?role=Supervisor');
        $response->assertStatus(403);
    }

    public function testDeleteFileOfForm(){
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $currentVisit['studyName'] );
        AuthorizationTools::addAffiliatedCenter($currentUserId, $currentVisit['centerCode']);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $review->sent_files = ['41' => $currentVisit['studyName'].'/'.'attached_review_file'.'/'.'review_1_41.csv'];
        $review->save();
        $response = $this->delete('api/reviews/' . $review->id . '/file/41');
        $response->assertSuccessful();
    }

    public function testDeleteFileOfFormShouldFailNoRole(){
        $currentVisit = $this->createVisit();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $review = Review::factory()->userId($currentUserId)->visitId($currentVisit['visitId'])->studyName($currentVisit['studyName'])->create();
        $review->sent_files = ['41' => $currentVisit['studyName'].'/'.'attached_review_file'.'/'.'review_1_41.csv'];
        $review->save();
        $response = $this->delete('api/reviews/' . $review->id . '/file/41');
        $response->assertStatus(403);
    }


}

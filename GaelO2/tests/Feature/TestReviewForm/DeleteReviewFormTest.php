<?php

namespace Tests\Feature\TestReviewForm;

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

class DeleteReviewFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createVisit() {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET_0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $review = Review::factory()->reviewForm()->visitId($visit->id)->studyName($study->name)->validated()->create();
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id,
            'reviewId' => $review->id
        ];
    }

    public function testDeleteReviewForm(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->delete('api/reviews/'.$visitData['reviewId'], $payload)->assertStatus(200);

    }

    public function testDeleteReviewFormShouldFailNoReason(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => ''
        ];

        $this->delete('api/reviews/'.$visitData['reviewId'], $payload)->assertStatus(400);

    }

    public function testDeleteReviewFormShouldFailNoRole(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->delete('api/reviews/'.$visitData['reviewId'], $payload)->assertStatus(403);

    }

    public function testDeleteReviewFormShouldFailBecauseExistingAdjudication(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        Review::factory()->reviewForm()->visitId($visitData['visitId'])->studyName($visitData['studyName'])->adjudication()->validated()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->delete('api/reviews/'.$visitData['reviewId'], $payload)->assertStatus(403);

    }

    public function testDeleteAdjudicationeviewForm(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $review = Review::factory()->reviewForm()->visitId($visitData['visitId'])->studyName($visitData['studyName'])->adjudication()->validated()->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->delete('api/reviews/'.$review['id'], $payload)->assertStatus(200);

    }

}

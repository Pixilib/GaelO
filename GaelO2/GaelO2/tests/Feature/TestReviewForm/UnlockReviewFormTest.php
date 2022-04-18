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
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;

class UnlockReviewFormTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();
    }

    private function createVisit() {
        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('FDG')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $review = Review::factory()->reviewForm()->visitId($visit->id)->studyName($study->name)->create();
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id,
            'reviewId' => $review->id
        ];
    }

    public function testUnlockReview(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->patch('api/reviews/'.$visitData['reviewId'].'/unlock', $payload)->assertStatus(200);

    }

    public function testUnlockReviewFormShouldFailNoReason(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $studyName);

        $payload = [
            'reason' => ''
        ];

        $this->patch('api/reviews/'.$visitData['reviewId'].'/unlock', $payload)->assertStatus(400);

    }

    public function testUnlockReviewFormShouldFailNoRole(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $studyName);

        $payload = [
            'reason' => 'wrong from'
        ];

        $this->patch('api/reviews/'.$visitData['reviewId'].'/unlock', $payload)->assertStatus(403);

    }

}

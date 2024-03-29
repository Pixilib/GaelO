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

class CreateReviewFormTest extends TestCase
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
        return [
            'studyName'=>$study->name,
            'visitId' => $visit->id
        ];
    }

    public function testCreateReviewForm(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(201);

    }

    public function testCreateReviewFormShouldFailBecauseNotAwaitingAdjudication(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => true,
            'validated' => true
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(400);

    }

    public function testCreateReviewFormShouldFailBecauseNoRole(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(403);

    }

    public function testCreateReviewFormShouldFailBecauseNoValidatedStatus(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => false,
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(400);

    }

    public function testCreateReviewFormShouldFailedBecauseAlreadyCreated(){
        $visitData = $this->createVisit();

        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        Review::factory()->userId($currentUserId)->studyName($studyName)->visitId($visitId)->reviewForm()->create();

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(409);

    }

    public function testCreateReviewFormShouldFailedBecauseReviewNotAvailable(){
        $visitData = $this->createVisit();

        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $reviewStatusEntity = ReviewStatus::where('visit_id', $visitId)->where('study_name', $studyName)->sole();
        $reviewStatusEntity->review_available = false;
        $reviewStatusEntity->save();

        $payload = [
            'data' => ['comments' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/visits/'.$visitId.'/reviews?studyName='.$studyName, $payload)->assertStatus(403);

    }


}

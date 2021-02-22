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

class CreateReviewFormTest extends TestCase
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
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();
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
            'data' => ['lugano' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(201);

    }

    public function testCreateReviewFormShouldFailBecauseNotAwaitingAdjudication(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $payload = [
            'data' => ['lugano' => 'CR'],
            'adjudication' => true,
            'validated' => true
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(400);

    }

    public function testCreateReviewFormShouldFailBecauseNoRole(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        AuthorizationTools::actAsAdmin(false);

        $payload = [
            'data' => ['lugano' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(403);

    }

    public function testCreateReviewFormShouldFailBecauseNoValidatedStatus(){
        $visitData = $this->createVisit();
        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        $payload = [
            'data' => ['lugano' => 'CR'],
            'adjudication' => false,
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(400);

    }

    public function testCreateReviewFormShouldFailedBecauseAlreadyCreated(){
        $visitData = $this->createVisit();

        $studyName = $visitData['studyName'];
        $visitId = $visitData['visitId'];
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $studyName);

        Review::factory()->userId($currentUserId)->studyName($studyName)->visitId($visitId)->create();

        $payload = [
            'data' => ['lugano' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(409);

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
            'data' => ['lugano' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->post('api/studies/'.$studyName.'/visits/'.$visitId.'/reviews', $payload)->assertStatus(403);

    }


}

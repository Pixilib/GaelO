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
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;
use Tests\TestCase;

class CreateReviewFormTest extends TestCase
{
    use RefreshDatabase;
    private MockInterface $trackerSpy;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_REVIEWER, Mockery::any(), Mockery::any(), Constants::TRACKER_SAVE_REVIEWER_FORM, Mockery::any());
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

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

class ModifyReviewFormTest extends TestCase
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

        $study = Study::factory()->name('TEST')->create();
        $patient = Patient::factory()->studyName($study->name)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->name('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $this->review = Review::factory()->studyName($study->name)->visitId($visit->id)->create();
        $this->studyName = $study->name;
    }

    public function testModifyReviewForm()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(200);
    }

    public function testModifyReviewFormShouldFailNoRole()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(403);
    }

    public function testModifyReviewFormShouldFailNotOwnForm()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(403);
    }

    public function testModifyReviewFormShouldFailAlreadyValidated()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->validated = true;
        $this->review->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(403);
    }

    public function testModifyReviewFormShouldFailVisitNotAwaitingReview()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();
        $reviewStatus = $this->review->visit->reviewStatus;
        $reviewStatus->review_available = false;
        $reviewStatus->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false,
            'validated' => true
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(403);
    }

    public function testModifyReviewFormShouldFailVisitNotValidationDecision()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $this->review->user_id = $currentUserId;
        $this->review->save();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_REVIEWER, $this->studyName);

        $payload = [
            'data' => ['comment' => 'CR'],
            'adjudication' => false
        ];

        $this->put('api/reviews/' . $this->review->id, $payload)->assertStatus(400);
    }
}

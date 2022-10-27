<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class StudyReviewProgressionTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testGetReviewProgressionShouldFailNotSupervisor()
    {
        $review = Review::factory()->reviewForm()->validated()->create();

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->json('GET', '/api/studies/' . $review->study_name . '/review-progression');
        $answer->assertStatus(403);
    }

    public function testGetStudyReviewProgression()
    {
        $reviewerUser = User::factory()->create();
        $reviewerUser->delete();
        $visit = Visit::factory()->create();
        $review = Review::factory()->reviewForm()->userId($reviewerUser->id)->visitId($visit->id)->studyName($visit->patient->study_name)->validated()->create();

        ReviewStatus::factory()->visitId($visit->id)->studyName($review->study_name)->create();

        Role::factory()->studyName($review->study_name)->userId($reviewerUser->id)->roleName(Constants::ROLE_REVIEWER)->create();
        Role::factory()->studyName($review->study_name)->roleName(Constants::ROLE_REVIEWER)->count(5)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->study_name);

        $answer = $this->json('GET', '/api/studies/' . $review->study_name . '/review-progression');
        $answer->assertStatus(200);
        $answerArray = json_decode($answer->content(), true);

        $this->assertEquals(1, sizeof($answerArray[0]['reviewDoneBy']));
        $this->assertEquals(5, sizeof($answerArray[0]['reviewNotDoneBy']));
    }
}

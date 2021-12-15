<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\AuthorizationTools;

class StudyReviewProgressionTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    public function testGetReviewProgression() {
        $reviewerUser = User::factory()->create();
        $review = Review::factory()->reviewForm()->userId($reviewerUser->id)->validated()->create();
        ReviewStatus::factory()->visitId($review->visit->id)->studyName($review->study_name)->create();

        Role::factory()->studyName($review->study_name)->userId($reviewerUser->id)->roleName(Constants::ROLE_REVIEWER)->create();
        Role::factory()->studyName($review->study_name)->roleName(Constants::ROLE_REVIEWER)->count(5)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $review->study_name);

        $answer = $this->json('GET', '/api/visit-types/'.$review->visit->visitType->id.'/review-progression?studyName='.$review->study_name);

        $answer->assertStatus(200);
        $answerArray = json_decode($answer->content(), true);
        $this->assertEquals(1, sizeof($answerArray[0]['reviewDoneBy']));
        $this->assertEquals(5, sizeof($answerArray[0]['reviewNotDoneBy']));

    }

    public function testGetReviewProgressionShouldFailNotSupervisor() {
        $review = Review::factory()->reviewForm()->validated()->create();

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->json('GET', '/api/visit-types/'.$review->visit->visitType->id.'/review-progression?studyName='.$review->study_name);
        $answer->assertStatus(403);

    }


}

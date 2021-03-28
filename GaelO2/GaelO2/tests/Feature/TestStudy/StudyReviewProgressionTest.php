<?php

namespace Tests\Feature\TestStudy;

use App\GaelO\Constants\Constants;
use App\Models\Review;
use App\Models\Role;
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

    public function testCreateStudy() {

        $review = Review::factory()->reviewForm()->validated()->create();

        Role::factory()->studyName($review->study_name)->roleName(Constants::ROLE_REVIEWER)->count(5)->create();

        AuthorizationTools::actAsAdmin(false);

        $answer = $this->json('GET', '/api/studies/'.$review->study_name.'/visit-types/'.$review->visit->visitType->id.'/review-progression');
        //dd($answer);

    }


}

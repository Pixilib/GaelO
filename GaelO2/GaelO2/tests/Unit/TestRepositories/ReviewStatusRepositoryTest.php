<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\ReviewStatusRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\ReviewStatus;

class ReviewStatusRepositoryTest extends TestCase
{
    private ReviewStatusRepository $reviewStatus;

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->reviewStatus = new ReviewStatusRepository(new ReviewStatus());
    }

    public function testGetReviewStatus()
    {

        $reviewStatus = ReviewStatus::factory()->create();
        $visit = $reviewStatus->visit;
        $study = $reviewStatus->study;

        $reviewStatusEntity = $this->reviewStatus->getReviewStatus($visit->id, $study->name);
        $this->assertEquals(boolval($reviewStatusEntity['review_available']), $reviewStatus->review_available);
    }

    public function testUpdateReviewStatus()
    {

        $reviewStatus = ReviewStatus::factory()->create();
        $this->reviewStatus->updateReviewStatus(
            $reviewStatus->visit_id,
            $reviewStatus->study_name,
            Constants::REVIEW_STATUS_DONE,
        );

        $review = ReviewStatus::get()->first();

        $this->assertEquals(Constants::REVIEW_STATUS_DONE, $review['review_status']);
    }

    public function testUpdateReviewConclusion(){

        $reviewStatus = ReviewStatus::factory()->create();
        $this->reviewStatus->updateReviewConclusion(
            $reviewStatus->visit_id,
            $reviewStatus->study_name,
            'Progression'
        );

        $review = ReviewStatus::get()->first();

        $this->assertEquals('Progression', $review['review_conclusion_value']);

    }

    public function testUpdateReviewAvailability(){

        $reviewStatus = ReviewStatus::factory()->create();

        $this->reviewStatus->updateReviewAvailability(
            $reviewStatus->visit_id,
            $reviewStatus->study_name,
            false
        );

        $review = ReviewStatus::get()->first();

        $this->assertEquals(false, boolval($review['review_available']));

    }
}

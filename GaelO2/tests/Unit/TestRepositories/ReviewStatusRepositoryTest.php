<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\ReviewStatusRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\ReviewStatus;

class ReviewStatusRepositoryTest extends TestCase
{
    private ReviewStatusRepository $reviewStatus;
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
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

    public function testUpdateReviewStatusAndConclusion(){

        $reviewStatus = ReviewStatus::factory()->create();
        $this->reviewStatus->updateReviewStatusAndConclusion(
            $reviewStatus->visit_id,
            $reviewStatus->study_name,
            Constants::REVIEW_STATUS_DONE,
            'Progression',
            ['liver'=> 3.54]
        );

        $review = ReviewStatus::get()->first();

        $this->assertEquals('Progression', $review['review_conclusion_value']);
        $this->assertEquals(Constants::REVIEW_STATUS_DONE, $review['review_status']);
        $this->assertIsArray($review['target_lesions']);

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

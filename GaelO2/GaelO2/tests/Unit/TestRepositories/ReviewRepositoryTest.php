<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\ReviewRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Review;
use App\Models\Visit;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReviewRepositoryTest extends TestCase
{
    private ReviewRepository $reviewRepository;

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
        $this->reviewRepository = new ReviewRepository(new Review());


    }

    public function testGetInvestigatorForm(){

        $review= Review::factory()->create();
        $visit = $review->visit;

        $investigatorForm = $this->reviewRepository->getInvestigatorForm($visit->id);

        $this->assertArrayHasKey('review_data', $investigatorForm);

    }


    public function testUnlockInvestigatorForm() {

        $review = Review::factory()->validated()->create();
        $visit = $review->visit;

        $this->reviewRepository->unlockInvestigatorForm($visit->id);

        $updatedReview = Review::find($review->id);

        $this->assertFalse(boolval($updatedReview['validated']));


    }

    public function testCreateReview(){
        $visit = Visit::factory()->create();
        $studyName = $visit->patient->study->name;

        $reviewId = $this->reviewRepository->createReview(true, $visit->id, $studyName, 1, ['comment'=>'PR'], true);

        $this->assertEquals(1, $reviewId);

        $reviewEntity = Review::find($reviewId)->toArray();
        $this->assertEquals('PR', $reviewEntity['review_data']['comment']);
    }

    public function testUpdateReview(){

        $review = Review::factory()->create();

        $this->reviewRepository->updateReview($review->id, 1, ['comment'=>'PR'] ,true);

        $reviewEntity = Review::find( $review->id )->toArray();

        $this->assertEquals('PR', $reviewEntity['review_data']['comment']);
    }

    public function testDeleteReview(){

        $review = Review::factory()->create();

        $this->reviewRepository->delete($review->id);

        $this->expectException(ModelNotFoundException::class);
        Review::findOrFail($review->id);
    }


}

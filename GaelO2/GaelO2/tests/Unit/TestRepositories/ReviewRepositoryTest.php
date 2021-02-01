<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\ReviewRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Review;

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


}

<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Repositories\ReviewRepository;
use App\Http\Controllers\ReviewController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Review;
use App\Models\Study;
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

    public function testUnlockReview(){
        $review = Review::factory()->validated()->create();
        $this->reviewRepository->unlockReviewForm($review->id);
        $updatedReview = Review::find($review->id);
        $this->assertFalse(boolval($updatedReview->validated));
    }

    public function testGetReviewFormForStudyVisitUser(){
        $review = Review::factory()->count(5)->create();
        $reviewAnswer = $this->reviewRepository->getReviewFormForStudyVisitUser($review->first()->study_name, $review->first()->visit_id, $review->first()->user_id);
        $this->assertEquals($review->first()->review_date, $reviewAnswer['review_date']);
    }

    public function testIsExistingFormForStudyVisitUser(){
        $review = Review::factory()->count(5)->create();
        $reviewAnswer = $this->reviewRepository->isExistingFormForStudyVisitUser($review->first()->study_name, $review->first()->visit_id, $review->first()->user_id);
        //Ask the wrong study
        $reviewAnswer2 = $this->reviewRepository->isExistingFormForStudyVisitUser($review->last()->study_name, $review->first()->visit_id, $review->first()->user_id);
        $this->assertTrue($reviewAnswer);
        $this->assertFalse($reviewAnswer2);
    }

    public function testGetReviewForStudyVisit(){
        $studies = Study::factory()->count(2)->create();
        $visit = Visit::factory()->count(2)->create();

        //Add review to a study that should not be selected
        Review::factory()->studyName($studies->last()->name)->visitId($visit->first()->id)->validated()->count(8)->create();
        Review::factory()->studyName($studies->last()->name)->visitId($visit->first()->id)->validated()->count(10)->create();
        //Add review potentially selected (good study and validated or not)
        Review::factory()->studyName($studies->first()->name)->visitId($visit->first()->id)->count(3)->create();
        Review::factory()->studyName($studies->first()->name)->visitId($visit->first()->id)->validated()->count(7)->create();

        $results = $this->reviewRepository->getReviewsForStudyVisit($studies->first()->name, $visit->first()->id, false);
        $results2 = $this->reviewRepository->getReviewsForStudyVisit($studies->first()->name, $visit->first()->id, true);

        $this->assertEquals(10, sizeof($results));
        $this->assertEquals(7, sizeof($results2));

    }

    public function testGetValidatedReviewsForStudyVisitType(){
        $studies = Study::factory()->count(2)->create();
        //Add review to a study that should not be selected
        Review::factory()->studyName($studies->first()->name)->count(10)->create();

        //Add review in the targeted study (create 2 visit, will have different visitType, only one should be selected)
        $visit = Visit::factory()->count(2)->create();
        Review::factory()->studyName($studies->last()->name)->visitId($visit->last()->id)->count(5)->create();
        //Create non targeted review for the visitType (local form or review non validated)
        Review::factory()->studyName($studies->last()->name)->visitId($visit->first()->id)->reviewForm()->count(3)->create();
        Review::factory()->studyName($studies->last()->name)->visitId($visit->first()->id)->count(3)->validated()->create();
        //create Targeted review, non local and validated
        Review::factory()->studyName($studies->last()->name)->visitId($visit->first()->id)->reviewForm()->validated()->count(10)->create();

        $results = $this->reviewRepository->getUsersHavingReviewedForStudyVisitType($studies->last()->name, $visit->first()->visitType->id);
        $this->assertArrayHasKey($visit->first()->visitType->id, $results);
        $this->assertEquals(10, sizeof($results[$visit->first()->visitType->id]) );

    }

    public function testGetReviewFromVisitIdArrayAndStudyName(){

        $study = Study::factory()->create();
        $visit = Visit::factory()->count(2)->create();

        $reviews = Review::factory()->studyName($study->name)->visitId($visit->first()->id)->reviewForm()->validated()->count(7)->create();
        Review::factory()->studyName($study->name)->visitId($visit->last()->id)->validated()->count(3)->create();

        $reviews->first()->delete();

        $reviewData = $this->reviewRepository->getReviewFromVisitIdArrayStudyName([$visit->first()->id, $visit->last()->id], $study->name, true);

        $this->assertEquals(10, sizeof($reviewData));
    }


}

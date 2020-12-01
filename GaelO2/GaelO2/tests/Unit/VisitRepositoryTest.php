<?php

namespace Tests\Unit;

use App\GaelO\Repositories\VisitRepository;
use App\Patient;
use App\Review;
use App\ReviewStatus;

use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VisitRepositoryTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations() {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

    }

    protected function setUp() : void{
        parent::setUp();
        $this->visitRepository = new VisitRepository();
        Artisan::call('passport:install');

        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class, 5)->create([ 'patient_code_prefix' => 1234])->first();
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);


    }

    private function createVisit(bool $reviewAvailable){
        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient->code,
        'visit_type_id' => $this->visitType->id,
        'status_done' => 'Done']);

        factory(ReviewStatus::class)->create([
            'visit_id' => $visit->id,
            'study_name'=> $this->study->name,
            'review_available'=>$reviewAvailable
        ]);

        return $visit;
    }

    public function testReviewAvailableForUser(){
        $visit = $this->createVisit(true);
        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(true, $availableForUser);
        $this->assertEquals(1, sizeof($answer));
    }

    public function testReviewAvailableForUserEvenDraftStarted(){

        $visit = $this->createVisit(true);

        factory(Review::class)->create([
            'visit_id' => $visit->id,
            'study_name' => $this->study->name,
            'user_id'=>1,
            'validated'=>false
        ]);

        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(true, $availableForUser);
        $this->assertEquals(1, sizeof($answer));

    }

    public function testReviewNotAvailableForUserWhileVisitReviewStillAvailable(){
        $visit = $this->createVisit(true);

        factory(Review::class)->create([
            'visit_id' => $visit->id,
            'study_name' => $this->study->name,
            'user_id'=>1,
            'validated'=>true
        ]);

        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(false, $availableForUser);
        $this->assertEquals(0, sizeof($answer));
    }

    public function testReviewNotAvailableForUserAsNotAvailableForReview(){
        $visit = $this->createVisit(false);
        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $this->assertEquals(0, sizeof($answer));
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(false, $availableForUser);

    }

}

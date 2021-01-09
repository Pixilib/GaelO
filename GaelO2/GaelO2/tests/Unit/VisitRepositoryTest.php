<?php

namespace Tests\Unit;

use App\GaelO\Repositories\VisitRepository;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;

use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
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
        $this->patient2 = factory(Patient::class)->create(['code' => 12341234123413, 'study_name' => $this->study->name, 'center_code' => 0]);


    }

    private function createVisit($patientEntity, bool $reviewAvailable){
        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $patientEntity->code,
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
        $visit = $this->createVisit($this->patient, true);
        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(true, $availableForUser);
        $this->assertEquals(1, sizeof($answer));
    }

    public function testReviewAvailableForUserEvenDraftStarted(){

        $visit = $this->createVisit($this->patient, true);

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
        $visit = $this->createVisit($this->patient, true);

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
        $visit = $this->createVisit($this->patient, false);
        $answer = $this->visitRepository->getVisitsAwaitingReviewForUser($this->study->name, 1);
        $this->assertEquals(0, sizeof($answer));
        $availableForUser = $this->visitRepository->isVisitAvailableForReview($visit->id, $this->study->name, 1);
        $this->assertEquals(false, $availableForUser);

    }

    public function testGetPatientHavingOneAwaitingReview(){
        $visit = $this->createVisit($this->patient, true);
        $visit = $this->createVisit($this->patient2, true);
        $answer = $this->visitRepository->getPatientsHavingAtLeastOneAwaitingReviewForUser($this->study->name, 1);
        //dd($answer);
    }

}

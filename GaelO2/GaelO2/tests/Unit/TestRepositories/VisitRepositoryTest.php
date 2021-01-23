<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\VisitRepository;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\User;
use Tests\TestCase;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VisitRepositoryTest extends TestCase
{
    private VisitRepository $visitRepository;

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
    }

    public function testCreateVisit(){
        $study = Study::factory()->create();
        $patient = Patient::factory()->create();
        $visitType = VisitType::factory()->create();
        $user = User::factory()->create();

        $this->visitRepository->createVisit($study->name, $user->id, $patient->code, null, $visitType->id,
        Constants::VISIT_STATUS_DONE, null, Constants::INVESTIGATOR_FORM_DONE, Constants::QUALITY_CONTROL_NOT_DONE);

        $visits = Visit::get();
        $this->assertEquals(1, $visits->count());
    }

    public function testIsExistingVisit(){
        $visit = Visit::factory()->create();
        $answerExisting = $this->visitRepository->isExistingVisit($visit->patient_code, $visit->visitType->id);
        $visitType = VisitType::factory()->create();
        $answerNotExisting = $this->visitRepository->isExistingVisit($visit->patient_code, $visitType->id);
        $this->assertTrue($answerExisting);
        $this->assertFalse($answerNotExisting);
    }

    public function testUpdateUploadStatus(){
        $visit = Visit::factory()->create();
        $this->visitRepository->updateUploadStatus($visit->id, Constants::UPLOAD_STATUS_DONE);
        $updatedVisit = Visit::find($visit->id);
        $this->assertEquals(Constants::UPLOAD_STATUS_DONE, $updatedVisit->upload_status);
    }

    public function testGetVisitContext(){

        $visit = Visit::factory()->create();
        $visitContext = $this->visitRepository->getVisitContext($visit->id);
        $this->assertArrayHasKey('visit_type', $visitContext);
        $this->assertArrayHasKey('visit_group', $visitContext['visit_type']);
        $this->assertArrayHasKey('patient', $visitContext);

    }

    public function testUpdateReviewAvailability(){

        $visit = Visit::factory()->create();
        $study = Study::factory()->create();
        ReviewStatus::factory()->visitId($visit->id)->studyName($study->name)->create();
        $this->visitRepository->updateReviewAvailability($visit->id, $study->name, true);

        $reviewStatus = ReviewStatus::where('study_name', $study->name)->where('visit_id', $visit->id)->firstOrFail();
        $this->assertTrue(boolval($reviewStatus->review_available));

    }

    public function testGetPatientVisits(){

        $study = Study::factory()->create();
        $patient = Patient::factory()->studyName($study->name)->create();

        VisitGroup::factory()->studyName($study->name);
        $visit = Visit::factory()->patientCode($patient->code)->create();

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

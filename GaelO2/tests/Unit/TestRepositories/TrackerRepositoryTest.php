<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\ReviewStatus;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Tracker;
use App\Models\Visit;

class TrackerRepositoryTest extends TestCase
{
    private TrackerRepository $trackerRepository;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->trackerRepository = new TrackerRepository(new Tracker());
    }

    public function testGetTrackerOfRole()
    {
        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->actionType(Constants::TRACKER_DELETE_VISIT)->count(5)->create();

        $answer = $this->trackerRepository->getTrackerOfRole(Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(3, sizeof($answer));
    }

    public function testGetTrackerOfRoleAndStudy()
    {
        $study1 = Study::factory()->create();
        $study2 = Study::factory()->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study1->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study1->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(5)->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study2->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(7)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study2->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(9)->create();

        $answer = $this->trackerRepository->getTrackerOfRoleAndStudy($study1->name, Constants::ROLE_INVESTIGATOR, true);
        $this->assertEquals(3, sizeof($answer));
    }

    public function testGetTrackerOfVisit()
    {

        $visit = Visit::factory()->create();
        $visit2 = Visit::factory()->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($visit->patient->study_name)->visitId($visit->id)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_CONTROLLER)->studyName($visit->patient->study_name)->visitId($visit->id)->actionType(Constants::TRACKER_CORRECTIVE_ACTION)->count(3)->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($visit->patient->study_name)->visitId($visit2->id)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();

        $answer = $this->trackerRepository->getTrackerOfVisitId($visit->id, $visit->patient->study_name);
        $this->assertEquals(6, sizeof($answer));
    }

    //Vérifier présence entities
    public function testGetTrackerStudyAction()
    {

        $visit = Visit::factory()->create();
        $studyName = $visit->patient->study_name;
        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($studyName)->visitId($visit->id)->actionType(Constants::TRACKER_SAVE_REVIEWER_FORM)->count(5)->create();
        Tracker::factory()->role(Constants::ROLE_CONTROLLER)->studyName($studyName)->visitId($visit->id)->actionType(Constants::TRACKER_CORRECTIVE_ACTION)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->visitId($visit->id)->actionType(Constants::TRACKER_SAVE_REVIEWER_FORM)->count(3)->create();
        ReviewStatus::factory()->visitId($visit->id)->studyName($studyName)->create();


        $trackerEntities = $this->trackerRepository->getTrackerOfRoleActionInStudy(Constants::ROLE_INVESTIGATOR, Constants::TRACKER_SAVE_REVIEWER_FORM, $studyName);
        $this->assertEquals(5, sizeof($trackerEntities));
        $this->assertArrayHasKey('visit', $trackerEntities[0]);
        $this->assertArrayHasKey('visit_type', $trackerEntities[0]['visit']);
        $this->assertArrayHasKey('patient', $trackerEntities[0]['visit']);
        $this->assertArrayHasKey('visit_group', $trackerEntities[0]['visit']['visit_type']);
        $this->assertArrayHasKey('user', $trackerEntities[0]);
    }

    public function testGetTrackerOfMessages()
    {
        $study = Study::factory()->create();
        $studyName=$study->name;
        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_INVESTIGATOR)->actionType(Constants::TRACKER_SEND_MESSAGE)->count(3)->create();
        Tracker::factory()->studyName($studyName)->role(Constants::ROLE_SUPERVISOR)->actionType(Constants::TRACKER_SEND_MESSAGE)->count(5)->create();

        $answer = $this->trackerRepository->getTrackerOfMessages($studyName);
        $this->assertEquals(8, sizeof($answer));
    }
}

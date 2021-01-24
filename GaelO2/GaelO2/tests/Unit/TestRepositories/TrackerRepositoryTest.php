<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Tracker;
use App\Models\Visit;

class TrackerRepositoryTest extends TestCase
{
    private TrackerRepository $trackerRepository;

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
        $this->trackerRepository = new TrackerRepository(new Tracker());
    }

    public function testGetTrackerOfRole() {
        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->actionType(Constants::TRACKER_DELETE_VISIT)->count(5)->create();

        $answer = $this->trackerRepository->getTrackerOfRole(Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(3, sizeof($answer));

    }

    public function testGetTrackerOfRoleAndStudy(){
        $study1 = Study::factory()->create();
        $study2 = Study::factory()->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study1->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study1->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(5)->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study2->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(7)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study2->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(9)->create();

        $answer = $this->trackerRepository->getTrackerOfRoleAndStudy($study1->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(3, sizeof($answer));

    }

    public function testGetTrackerOfStudy(){

        $study1 = Study::factory()->create();
        $study2 = Study::factory()->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study1->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study1->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(5)->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->studyName($study2->name)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(7)->create();
        Tracker::factory()->role(Constants::ROLE_SUPERVISOR)->studyName($study2->name)->actionType(Constants::TRACKER_DELETE_VISIT)->count(9)->create();

        $answer = $this->trackerRepository->getTrackerOfActionInStudy(Constants::TRACKER_DELETE_VISIT, $study1->name);
        $this->assertEquals(5, sizeof($answer));
    }

    public function testGetTrackerOfVisit(){

        $visit = Visit::factory()->create();
        $visit2 = Visit::factory()->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->visitId($visit->id)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();
        Tracker::factory()->role(Constants::ROLE_CONTROLLER)->visitId($visit->id)->actionType(Constants::TRACKER_CORRECTIVE_ACTION)->count(3)->create();

        Tracker::factory()->role(Constants::ROLE_INVESTIGATOR)->visitId($visit2->id)->actionType(Constants::TRACKER_UPLOAD_SERIES)->count(3)->create();

        $answer = $this->trackerRepository->getTrackerOfVisitId($visit->id);
        $this->assertEquals(6, sizeof($answer));
    }


}

<?php

namespace Tests\Feature\TestTools;

use App\GaelO\Constants\Constants;
use App\Models\Center;
use App\Models\Patient;
use App\Models\Review;
use App\Models\ReviewStatus;
use App\Models\Study;
use App\Models\Visit;
use App\Models\VisitGroup;
use App\Models\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;
use Tests\TestCase;
use Log;
class ToolsTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();

        $study = Study::factory()->name('TEST')->create();
        $center = Center::factory()->code(1)->create();
        $patient = Patient::factory()->studyName($study->name)->centerCode($center->code)->create();
        $visitGroup = VisitGroup::factory()->studyName($study->name)->modality('PT')->create();
        $visitType  = VisitType::factory()->visitGroupId($visitGroup->id)->name('PET0')->localFormNeeded()->create();
        $visit = Visit::factory()->patientId($patient->id)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $this->review = Review::factory()->studyName($study->name)->visitId($visit->id)->reviewForm()->create();
        $this->studyName = $study->name;
        $this->centerCode = $center->code;
        $this->patientId = $patient->id;
        $this->visitTypeId = $visitType->id;
    }

    public function testGetPatientsInStudyFromCenters() {
        $center = Center::factory()->code(2)->create();
        Patient::factory()->studyName($this->studyName)->centerCode($this->centerCode)->count(9)->create();
        Patient::factory()->studyName($this->studyName)->centerCode($center->code)->count(10)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['centerCodes']  = [ $this->centerCode, $center->code ];
        $validPayload['studyName'] = $this->studyName;

        $answer = $this->json('POST', 'api/tools/centers/patients-from-centers', $validPayload);
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);
        $this->assertEquals(20, sizeof($content));
    }

    public function testGetPatientsVisitsInStudy() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $validPayload['patientIds'] = [ $this->patientId ];
        $answer = $this->json('POST', 'api/tools/patients/visits-from-patients?studyName='.$this->studyName, $validPayload);
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);

    }

}

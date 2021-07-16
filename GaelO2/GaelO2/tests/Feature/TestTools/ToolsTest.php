<?php

namespace Tests\Feature\TestInvestigatorForm;

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
        $visit = Visit::factory()->patientCode($patient->code)->visitTypeId($visitType->id)->create();
        ReviewStatus::factory()->studyName($study->name)->visitId($visit->id)->reviewAvailable()->create();
        $this->review = Review::factory()->studyName($study->name)->visitId($visit->id)->reviewForm()->create();
        $this->studyName = $study->name;
        $this->centerCode = $center->code;
        $this->patientCode = $patient->code;
    }

    public function testGetCentersFromStudy(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->get('api/tools/studies/'.$this->studyName.'/centers')->assertSuccessful();

    }

    public function testGetPatientsInStudyFromCenters() {
        Patient::factory()->studyName($this->studyName)->centerCode($this->centerCode)->count(9)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();
        Patient::factory()->studyName($this->studyName)->count(10)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->validPayload = [ $this->centerCode ]; 
        $answer = $this->json('POST', 'api/tools/studies/'.$this->studyName.'/centers/patients', $this->validPayload); 
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);
        $this->assertEquals(10, sizeof($content));
    }

    public function testGetPatientsVisitsInStudy() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->studyName);

        $this->validPayload = [ $this->patientCode ]; 
        $answer = $this->json('POST', 'api/tools/studies/'.$this->studyName.'/patients/visits', $this->validPayload); 
        $answer->assertSuccessful();
        $content = json_decode($answer->content(), true);

    }

}
